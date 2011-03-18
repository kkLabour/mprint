<?php

/**
 * @desc mPrint Class File
 *
 * @author Morris Jencen O. Chavez <macinville@gmail.com>
 * @version 0.0.2
 *
 * @desc mPrint prints the desired elements in your HTML page.
 * Check out @link http://www.bennadel.com/blog/1591-Ask-Ben-Print-Part-Of-A-Web-Page-With-jQuery.htm for details.
 *
 * Installation:
 *
 * 1) Extract files under extensions directory
 *
 * @example
 *
 * <?php
 *      $this->widget('ext.mPrint.mPrint', array(
 *           'title' => 'title',        //the title of the document. Defaults to the HTML title
 *           'tooltip' => 'testing',    //tooltip message of the print icon. Defaults to 'print'
 *           'text' => 'Print Results', //text which will appear beside the print icon. Defaults to NULL
 *           'element' => '#page',      //the element to be printed.
 *           'exceptions' => array(     //the element/s which will be ignored
 *               '.summary',
 *               '.search-form'
 *           ),
 *           'publishCss' => true       //publish the CSS for the whole page?
 *       ));
 * ?>
 *
 * Changelogs:
 * 0.0.2
 *  - fixed the bug 'Missing argument 2 for CClientScript::registerCss()' by replacing registerCss with registerCssFile (thanks to joblo)
 *  - modified the property 'exemptions' to 'exceptions' for a more definitive term (thanks Gustavo)
 *  - added the property 'publishCss' bool, which will dictate whether to register the CSS file for the whole page (for the benefit of CTRL+P).
 * 0.0.1
 *  - initial release
 */

class mPrint extends CWidget {

    /**
    * @desc css string the css which will be used by the printed document.
     * Edit the file to taste.
    */
	public $css = 'mprint.css';

    /**
     * @desc title string title of the document to be printed.
     *  Defaults to the title of the HTML.
     */
    public $title = NULL;
    
    /**
     * @desc tooltip string tooltip message for the print icon
     *  Defaults to "print".
     */
    public $tooltip = "print";
    
    /**
     * @desc text string message which will appear beside the print icon
     */
    public $text;

    /**
     * @desc htmloptions array
     *  Yii-standard variable
     */
    public $htmlOptions = array();

    /**
     * @desc elements string html element (div or class) which will be printed
     */
    public $element = '#page';
    
    /**
     * @desc exceptions array html elements which will be exempted in printing
     */
    public $exceptions = array();

    /**
     * @desc assetsPath string path of its asset files
     */
    public $assetsPath;

    /**
     * @desc printerIcon string icon to be used for printing
     */
    public $printerIcon;

    /**
     * @desc publishCss bool whether to register the CSS file for the whole page (for the benefit of CTRL+P).
     *  Defaults to false.
     */
    public $publishCss = false;

    public function init() {
        $assets = dirname(__FILE__) .'/'. 'assets';
        $this->assetsPath = Yii::app()->getAssetManager()->publish($assets);
        $this->printerIcon = $this->assetsPath . '/'. 'printer.png';
        Yii::app()->getClientScript()->registerScriptFile($this->assetsPath. '/'.'mPrint.js');
        Yii::app()->clientScript->registerCoreScript('jquery');

        //to publish or not to publish? that is the question
        $this->publishCss ? Yii::app()->getClientScript()->registerCssFile($this->assetsPath. '/'.$this->css,"print"): '';
    }

    public function run() {
        //display the print icon
        $this->showPrintLink();

        //add the appropriate class which will be printed and ignored
        $this->addApprClass();

        // hook the event to the print icon
        $this->mPrint();

        //set some css...
        $this->someCss();
    }

    /**
     * @desc renders the link for printing the page
     */
    private function showPrintLink() {
        $text = "";
        if (isset($this->text))
            $text = "&nbsp;" . $this->text;
        echo CHtml::link('<img src="' . $this->printerIcon . '" title="' . $this->tooltip . '">' . $text, "", array_merge($this->htmlOptions, array('id' => 'mprint')));
    }

    /**
     * @desc adds the appropriate classes for included and ignored elements
     */
    private function addApprClass() {
        //add the class "mprint" to those elements which should be printed
        Yii::app()->clientScript->registerScript('addPrintClass', '
                $(function(){
                    $("' . $this->element . '").addClass("mprint");
                });
            ');

        //hide the elements which should not be printed (if any)
        if (count($this->exceptions)) {
            $hideElements = "";
            foreach ($this->exceptions as $index => $exemption)
                $hideElements .= '$("' . $exemption . '").addClass("hide-print");';
            //add the appropriate class which will be printed
            Yii::app()->clientScript->registerScript('hidePrintClass', '
                $(function(){
                    ' . $hideElements . '
                });
            ');
        }
    }

    /**
     * @desc the one calling our js file
     */
    public function mPrint() {
        //set the file name. Defaults to the title of the HTML
        $mac = isset($this->title) ? 'var documentName = "' . $this->title . '";' :
                'var documentName = document.title;';

        //give the link to the CSS file to be used by the report
        $mac .= 'var css="'.$this->assetsPath.'/'.$this->css.'";';

        //register the script
        Yii::app()->clientScript->registerScript('processPrint', '
            $(function(){
				// Hook up the print link.
				$( "#mprint" )
					.attr( "href", "javascript:void( 0 )" )
					.click(
						function(){
                            ' . $mac . '
                            // Print the DIV.
							$( ".mprint" ).print(documentName,css);

							// Cancel click event.
							return( false );
						}
                    );
                }
			);'
        );
    }

    /**
     * @desc some CSS...
     */
    private function someCss() {
        echo CHtml::css(".hide-print {display: none;}", "print");
        echo CHtml::css("#mprint {cursor: 'pointer';}", "screen");
    }
}

?>
