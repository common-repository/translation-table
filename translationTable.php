<?php
/*
Plugin Name: Translation Table 
Plugin URI: http://www.cbiconsulting.es
Description: Export / Import posts to a Excel2007 format to create a translation table (Herramientas/Tools) 
Version:  0.1
Author: AeMonge
Author URI: http://www.cbiconsulting.es
License: MIT 
*/

// Word Press Menu 
add_action('admin_menu', 'translation_table_menu');
define( 'TRASLATIONTABLE_PATH', plugin_dir_path(__FILE__) );

require_once TRASLATIONTABLE_PATH.'Classes/PHPExcel.php';
require_once TRASLATIONTABLE_PATH.'Classes/stack.php';
require_once TRASLATIONTABLE_PATH.'Classes/PHPExcel/IOFactory.php';

// Required for the PHPExcel to work properlly.
iconv_set_encoding('output_encoding', 'UTF-8');
iconv_set_encoding('input_encoding', 'UTF-8');
iconv_set_encoding('internal_encoding', 'UTF-8');

/*
 * SimpleHtmlParser
 *
 * @category PHPExcel
 * @copyright MIT license
 */
class SimpleHtmlParser
{ 
	/**
	 * Usefull vars to parse the html
	 */
	private $str;
	private $tagStack;

	/**
	 *	This function evaluates the HTML tag that follows the given LINE, starting in position POS while moving POS to the
	 *	end of the tag.
	 *
	 *	@param string $line the line/string to analise the first html supported tag
	 *	@param int &$pos the starting position of the detected tag. This position MUST be pointing to '<' in the given line
	 *	@return string $type the tag name. Supported tags, moreover return values are (b,s,i,1,2).Note 1 is for H1, 2 for  H2
	 * 	@return bool $closingtag true if it's a closing tag, eg. </b>
	*/
	private function getTagProps($line, &$pos, &$type, &$closingTag){
		$pos++; //The pointer (pos) is pointing '<'; so must move forward
		$closingTag= false;
		if ($line[$pos] == '/'){
			$closingTag= true;
			$pos++;
		}
		if ($line[$pos] == 'h')
			$pos++;

		$type= $line[$pos]; // b, s, i, 1 or 2

		while ($line[$pos] != '>') //Set the pointer after the tag, eg. <*>|
			$pos++;

	}

	/*
	 * It writes the collected text under $this->str with the collected styles under $this->tagStack
	 * @param PHPExcel_RichText &$obj the PHPExcel object to write in
	 */
	private function writeWithStyle(&$obj){
		if ($this->str == '')
			return(0);

		$tmpStack= $this->tagStack->copy();
		$obj2= $obj->createTextRun($this->str);

		while (!($tmpStack->is_empty())){
			$t= $tmpStack->pop();
			switch($t){
				case 'b':
				case 's': 	//<strong>
					$obj2->getFont()->setBold(true);
					break;
				case 'i':
					$obj2->getFont()->setItalic(true);
					break;
				case '1':	//h1
					$obj2->getFont()->setSize(20);
					break;
				case '2':	//h2
					$obj2->getFont()->setSize(16);
					break;
			}
		}
		$this->str= '';
		//Reset Styles
		$obj->createTextRun(' ');
	}

	/**
	 * Create a new SimpleHtmlParser
	 */
	public function __construct(){
		$this->str= '';
		$this->tagStack = new Stack();
	}

	/*
	 * Prepares the string to be correctly parse to the PHPExcel object
	 * @return string $content the content correctly formated to PHPExcel mode
	 */
	public function prepare(&$content, $opt= true){
        $content= strip_tags($content, '<br /><p><i><b><h1><h2><strong>');
        $content= strtr($content, array('<p>' => '', '</p>' => '', '<br />' => '' ));
		if ($opt)
			$content= utf8_decode($content);
	}

	/*
	 * It writes in RFT format the line to the PHPExcel object
	 * 
	 * @param string $line the string/line to analise and write
	 * @return PHPExcel_RichText	&$obj the PHPExcel rich text objet to write in
	 */
	public function parseXLS($line){
		$obj= new PHPExcel_RichText();
		$i= 0;	
		
		while ($i < strlen($line)){
			if ( $this->tagStack->is_empty() && ( $line[$i] != '<')  ){
				$obj->createText($line[$i]);
			}else if( $line[$i] == '<' ){
				$this->getTagProps($line, $i, $tag, $closing); 
				$this->writeWithStyle($obj);

				if ( $closing )
					$this->tagStack->pop();
				else //Opening
					$this->tagStack->push($tag);
			}else{
				$this->str.= $line[$i];
			}
			$i++;
		}
	return ($obj);
	}

	/*
	 * Recives a Cell from the PHPExcel lib, and returns the HTML from the RTF text of the cell
	 *
	 * @param PHPExcel_Cell $cell the cell that contains text (or rich text) content to be parsed
	 * @return the parsed HTML
	 */
	public function parseHTML($cell){
		$html= ''; 
		if ( (!is_null($cell->getValue())) && ( gettype($cell->getValue()) == 'object' ) ){
			$objRun = $cell->getValue()->getRichTextElements();
			foreach ($objRun as $i => $v){
				if (get_class($v) == 'PHPExcel_RichText_TextElement'){ //Not Rich Formated; againsts the odds.
					$html.= $v->getText();
				}else{													// Rich Formated !
					if ($v->getFont()->getBold())			$html.= "<b>";
					if ($v->getFont()->getItalic())			$html.= "<i>";

					if ($v->getFont()->getSize() == 20)		$html.= '<h1>'.$v->getText().'</h1>';
					else if ($v->getFont()->getSize() == 16)$html.= '<h2>'.$v->getText().'</h2>';
					else									$html.= $v->getText();

					if ($v->getFont()->getItalic())			$html.= "</i>";
					if ($v->getFont()->getBold())			$html.= "</b>";
				}
			}
		}
		return $html;
	}

}


/*
 * Exports the posted pages under WP, with the language plugin qTransalete
 * to a Excel 2007 sheet, row per post, and columns per translation
 */
function translation_table_export(){
    global $wpdb;
    // Fetch the post from DB
    $query = ' SELECT id, post_title as title, post_content as content
               FROM '.$wpdb->posts.' 
               WHERE post_type = "page"
                AND  post_status    = "publish"
               ORDER BY id
    ';
    $posts= $wpdb->get_results($query);

    /** PHPExcel */
    $objPHPExcel = new PHPExcel();
    $objPHPExcel->getProperties()->setCreator("Translation Table")
                                 ->setLastModifiedBy("Translation Table")
                                 ->setTitle("WP Pages for translation")
                                 ->setSubject("WP Pages (id, title's, content's) bilingual")
                                 ->setDescription("WP Pages (id, title's, content's) bilingual")
                                 ->setKeywords("openxml php import export multilingual table")
                                 ->setCategory("None");
   // Imprimir la cabecera del EXCEL
   $objPHPExcel->setActiveSheetIndex(0)
               ->setCellValue('A1','ID')
               ->setCellValue('B1','Título')
               ->setCellValue('C1','Title (english)')
               ->setCellValue('D1','Cuerpo')
               ->setCellValue('E1','Body (english)');
    
    foreach( $posts as $ix => $post){
		$both= explode("<!--:-->", $post->content);
		$content= $both[0];	$content_en= $both[1];

		$both= explode("<!--:-->", $post->title);
		$title= $both[0];	$title_en= $both[1];


		$parser= new SimpleHtmlParser();
		$parser->prepare($content); $parser->prepare($content_en);
		$parser->prepare($title, false);	$parser->prepare($title_en, false);

		$objRichText= $parser->parseXLS($content);
		$objRichText_en= $parser->parseXLS($content_en);

        $objPHPExcel->setActiveSheetIndex(0)
                    ->setCellValue('A'.($ix+2),$post->id)
                    ->setCellValue('B'.($ix+2),$title)
                    ->setCellValue('C'.($ix+2),$title_en)
                    ->setCellValue('D'.($ix+2),$objRichText)
                    ->setCellValue('E'.($ix+2),$objRichText_en);
	}

    // Save Excel 2007 file
    $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
    $objWriter->save(TRASLATIONTABLE_PATH.'temp.xlsx');
	chmod(TRASLATIONTABLE_PATH.'temp.xlsx', 0777);
    
	//JavaRedirect to offer File
	?><script type="text/javascripT">
		document.location= "../wp-content/plugins/translation_table/temp.xlsx";
	</script><?php
}

/*
 * Imports the Excel 2007 sheet (previouslly exported) to the posts
 * with the language plugin qTransalete
 */
function translation_table_import(){
	if ( ($_FILES['xlsx']['type'] == 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' ) && (move_uploaded_file($_FILES['xlsx']['tmp_name'], TRASLATIONTABLE_PATH.'temp.xlsx')) ) {
		chmod(TRASLATIONTABLE_PATH.'temp.xlsx', 0777);

		global $wpdb;
		$objRichTxt= new PHPExcel_RichText();
		$objReader = PHPExcel_IOFactory::createReader('Excel2007');
		$objPHPExcel = $objReader->load(TRASLATIONTABLE_PATH.'temp.xlsx');
		$parser= new SimpleHtmlParser();

		foreach ($objPHPExcel->getWorksheetIterator() as $worksheet) {
			foreach ($worksheet->getRowIterator() as $ix => $row) {
				//Skip the header
				if ($ix > 1){
					$cellIterator = $row->getCellIterator();
					$cellIterator->setIterateOnlyExistingCells(false); // Loop all cells, even if it is not set
					foreach ($cellIterator as $cell) 
						if (!is_null($cell)) {
							if ($cell->getColumn() == 'B')
								$title= '<!--:es-->'.$cell->getValue().'<!--:-->';
							if ($cell->getColumn() == 'C')
								$title.= '<!--:en-->'.$cell->getValue().'<!--:-->';
							if ($cell->getColumn() == 'D')
								$content= '<!--:es-->'.$parser->parseHTML($cell).'<!--:--> ';
							if ($cell->getColumn() == 'E')
								$content.= '<!--:en--> '.$parser->parseHTML($cell).'<!--:-->';
							if ($cell->getColumn() == 'A')
								$id= $cell->getValue();
						}
					// UpDate (one per one)
					$wpdb->update( $wpdb->posts, array( 'post_title' => $title,
														'post_content' => $content), array( 'ID' => $id ) );
				}
			}
		}
		?> <script type="text/javascript">alert('Imported!');</script><?php

	} else{
    	echo "There was an error uploading the file, please try again!";
	}
}

function translation_table_menu() {
    add_management_page( 'Translation Table', 'Translation Table', 10, 'translation_table', 'show_translation_table' );
}

function show_translation_table() {
    if ( !current_user_can( 'manage_options' ) )  {
        wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
    }
    
	?><div class="wrap">
        <a href="?page=<?php echo $_GET['page']; ?>&opt=export" alt="export"><h1>Export</h1></a>
		<form enctype="multipart/form-data" method="POST" action="tools.php?page=translation_table"  >
			<input type="hidden" name="opt" value="import" />
			<input type="file" name="xlsx" />
			<input type="submit" name="opt" value="import" />
		</form>
    </div><?php

    if ($_REQUEST['opt'] == 'export')
        translation_table_export();
    else if ($_REQUEST['opt'] == 'import')
        translation_table_import();
} ?>
