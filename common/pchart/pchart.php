<?php
require_once('ppc_report_chart.php');
require_once('pChart/class/pData.class.php');
require_once('pChart/class/pDraw.class.php');
require_once('pChart/class/pImage.class.php');
require_once('pChart/class/pPie.class.php');

class pchart   
{
	public $path = '';
	public $height = '';
	public $width = '';
	public $data_points = '';
	public $sub_header = '';
	public $main_header = '';
	
	public $myData;
	public $myPicture;
	public $mySetting;
	
	function __construct($info)
	{
		$this->path 		= isset($info['path'])   	  ? 	$info['path'] 			: '';
		$this->height 		= isset($info['height']) 	  ? 	$info['height'] 		: 400;
		$this->width 		= isset($info['width']) 	  ? 	$info['width']	 		: 600;
		$this->data_points 	= isset($info['data_points']) ? 	$info['data_points'] 	: array();
		$this->sub_header 	= isset($info['sub_header'])  ? 	$info['sub_header'] 	: '';
		$this->main_header 	= isset($info['main_header']) ? 	$info['main_header'] 	: '';
		
		
		/* Palette colors of the series */
		$this->Palette = array("0"=>array("R"=>113,"G"=>39,"B"=>61,"Alpha"=>100),
                 "1"=>array("R"=>0,"G"=>50,"B"=>60,"Alpha"=>100),                 
                 "2"=>array("R"=>0,"G"=>101,"B"=>121,"Alpha"=>100),
                 "3"=>array("R"=>140,"G"=>141,"B"=>142,"Alpha"=>100),
				 "4"=>array("R"=>89,"G"=>135,"B"=>155,"Alpha"=>100),
                 "5"=>array("R"=>0,"G"=>0,"B"=>0,"Alpha"=>100),
                 "6"=>array("R"=>54,"G"=>118,"B"=>125,"Alpha"=>100),
                 "7"=>array("R"=>109,"G"=>203,"B"=>208,"Alpha"=>100),
				 "8"=>array("R"=>232,"G"=>236,"B"=>236,"Alpha"=>100));
				 		
	}	
	
	public function get_font_path($font) {
		return __DIR__ . '/pChart/fonts/' . $font;
	} 

		
	public function __setDataPoints(){ 
		
		$this->myData = new pData();
		$i = 0;
		
		// get info about primary axis as we loop over data sets
		$primary_axis_info = array(
			'formats' => array(),
			'cols' => array()
		);
		foreach ($this->data_points as $data_point)
		{
			$this->myData->addPoints($data_point['data'], $data_point['info']['display']);
			$this->myData->setLabelFormatType($data_point['info']['display'], $data_point['info']['format']);
			
			// horizontal axis
			if ($data_point['info']['axis'] == 'horizontal')
			{
				switch ($data_point['info']['display'])
				{
					case ('daily'):   $label = 'Day'; break;
					case ('weekly'):  $label = 'Week'; break;
					case ('monthly'): $label = 'Month'; break;
					default:          $label = util::display_text($data_point['info']['display']); break;
				}
				$this->myData->setSerieDescription($data_point['info']['display'], $label);
				$this->myData->setAbscissa($data_point['info']['display']);
				$this->myData->setAbscissaName($label);
			}
			// vertical axis
			else
			{
				$this->myData->setSerieDescription($data_point['info']['display'], $data_point['info']['display'] .'  ');
				$this->myData->setPalette($data_point['info']['display'], $this->Palette[$i]);
				$i++;
				
				if ($data_point['info']['is_secondary_axis'] == true)
				{
					$this->myData->setSerieOnAxis($data_point['info']['display'], 1);
					$this->myData->setAxisPosition(1, AXIS_POSITION_RIGHT);
					$this->myData->setAxisName(1, $data_point['info']['display']); 
				}
				else
				{
					$format = util::unnull($data_point['info']['format'], $data_point['info']['format_excel'], '');
					$primary_axis_info['formats'][] = $format;
					$primary_axis_info['cols'][] = $data_point['info']['display'];
				}
			}
		}
		
		// if there is just one data set on primary axis, label with that name
		// otherwise check format
		// our format types include percent, dollar, and generic numbers (0 to 2 decimals, etc)
		// see what types we have in this data set
		// if we only have dollars or only percents, label axis accordingly
		// otherwise don't label
		if (count($primary_axis_info['cols']) == 1)
		{
			$this->myData->setAxisName(0, $primary_axis_info['cols'][0]);
		}
		else
		{
			$primary_axis_formats = array_unique($primary_axis_info['formats']);
			if (count($primary_axis_formats) == 1)
			{
				$format = $primary_axis_formats[0];
				if (strpos($format, 'dollar') !== false)
				{
					$this->myData->setAxisName(0, 'US Dollars');
				}
				else if (strpos($format, 'percent') !== false)
				{
					$this->myData->setAxisName(0, 'Percent');
				}
			}
		}
	}
	
	public function render(){  
		
		$this->__setDataPoints();
		
		$this->myPicture = new pImage($this->width, $this->height,$this->myData); 
		
		/* Draw the background */ 
		$this->mySetting = array("R"=>255, "G"=>250, "B"=>250); 
		$this->myPicture->drawFilledRectangle(0,0,$this->width,$this->height,$this->mySetting); 
		
		/* Overlay with a gradient */
		//$this->mySetting = array("StartR"=>0, "StartG"=>109, "StartB"=>117, "EndR"=>1, "EndG"=>138, "EndB"=>68, "Alpha"=>50); 
		//$this->myPicture->drawGradientArea(0,0,$this->width,$this->height,DIRECTION_VERTICAL,$this->mySetting); 
		
		/* Draw the dark horizontal title bar on top */
		$this->myPicture->drawGradientArea(0,0,$this->width,20,DIRECTION_VERTICAL,array("StartR"=>0,"StartG"=>0,"StartB"=>0,"EndR"=>50,"EndG"=>50,"EndB"=>50,"Alpha"=>80));
		
		/* Add a border to the picture */ 
		$this->myPicture->drawRectangle(0,0,$this->width-1,$this->height-1,array("R"=>0,"G"=>0,"B"=>0)); 
		  
		/* Write the picture title */  
		$this->myPicture->setFontProperties(array("FontName"=>$this->get_font_path('Silkscreen.ttf'),"FontSize"=>6)); 
		$this->myPicture->drawText(10,13,$this->sub_header,array("R"=>255,"G"=>255,"B"=>255)); 
		
		/* Write the chart title */  
		$this->myPicture->setFontProperties(array("FontName"=>$this->get_font_path('Forgotte.ttf'),"FontSize"=>11)); 
		$this->myPicture->drawText(250,55,$this->main_header,array("FontSize"=>20,"Align"=>TEXT_ALIGN_BOTTOMMIDDLE)); 
			
	}

}

/*
 * Line Chart class
 * Main function: render() --> returns a line chart
 * It will first invoke render() from generic parent class pchart
 */
class line_chart extends pchart
{
	public function render()
	{		
		parent::render();
		
		/* Draw the scale and the chart */ 
		$this->myPicture->setGraphArea(90,90,$this->width-60,$this->height-60); 
		$this->myPicture->drawFilledRectangle(60,60,$this->width-60,$this->height-60,array("R"=>255,"G"=>255,"B"=>255,"Surrounding"=>-200,"Alpha"=>10));
		$this->myPicture->drawScale(array("DrawSubTicks"=>TRUE,'Mode'=>SCALE_MODE_START0));
		$this->myPicture->setShadow(TRUE,array("X"=>1,"Y"=>1,"R"=>0,"G"=>0,"B"=>0,"Alpha"=>10)); 
 
		$this->myPicture->drawLineChart(array("DisplaySize"=>13, "DisplayValues"=>TRUE,"DisplayColor"=>DISPLAY_AUTO));
		$this->myPicture->drawPlotChart(array("BorderSize"=>1, "Surrounding"=>40, "BorderAlpha"=>100, "PlotSize"=>2, "PlotBorder"=>TRUE, "DisplayColor"=>DISPLAY_AUTO));
		 
		$this->myPicture->setShadow(FALSE); 
		
		/* Write the chart legend */ 
		$this->myPicture->setFontProperties(array("FontName"=>$this->get_font_path('Forgotte.ttf'),"FontSize"=>12));
		$this->myPicture->drawLegend(400,10,array("Style"=>LEGEND_NOBORDER,"Mode"=>LEGEND_HORIZONTAL, "FontR" => 255, "FontG" => 250, "FontB" => 250));  
		
		/* Render the picture (choose the best way) */ 
		$this->myPicture->autoOutput($this->path);
	}
}

/*
 * Bar Chart class
 * Main function: render() --> returns a bar chart
 * It will first invoke render() from generic parent class pchart
 */
class bar_chart extends pchart 
{
	public function render(){
		 
		parent::render();
		
		/* Draw the scale  */ 
		$this->myPicture->setGraphArea(90,90,$this->width-60,$this->height-60);
		
		
		$this->myPicture->drawScale(array("DrawSubTicks"=>TRUE,'Mode'=>SCALE_MODE_START0)); 
		//$this->myPicture->drawScale(array("CycleBackground"=>TRUE,"DrawSubTicks"=>TRUE,"GridR"=>0,"GridG"=>0,"GridB"=>0,"GridAlpha"=>10,'Mode'=>SCALE_MODE_START0)); 
		
		
		/* Turn on shadow computing */  
		$this->myPicture->setShadow(TRUE,array("X"=>1,"Y"=>1,"R"=>0,"G"=>0,"B"=>0,"Alpha"=>10)); 
		
		/* Draw the chart */ 
		$settings = array("DisplaySize"=>14, "DisplayValues"=>TRUE, "Gradient"=>TRUE,"GradientMode"=>GRADIENT_EFFECT_CAN,"DisplayPos"=>LABEL_POS_TOP, "DisplayColor"=>DISPLAY_AUTO, "DisplayShadow"=>TRUE,"Surrounding"=>10);
		$this->myPicture->drawBarChart($settings); 
		
		/* Write the chart legend */
		$this->myPicture->setFontProperties(array("FontName"=>$this->get_font_path('Forgotte.ttf'),"FontSize"=>12));
		$this->myPicture->drawLegend(400,10,array("Style"=>LEGEND_NOBORDER,"Mode"=>LEGEND_HORIZONTAL, "FontR" => 255, "FontG" => 250, "FontB" => 250)); 
		
		
		
		/* Render the picture (choose the best way) */ 
		$this->myPicture->autoOutput($this->path);
	}	
}

/*
 * Pie Chart class
 * Main function: render() --> returns a pie chart
 * It will first invoke render() from generic parent class pchart
 */
class pie_chart extends pchart 
{
	public function render(){
		 
		parent::render();
		
		$this->myPicture->setGraphArea(90,90,$this->width-60,$this->height-60);
		
		/* Create the pPie object */ 
		$PieChart = new pPie($this->myPicture,$this->myData);
		
		for ($i = 0; $i<count($this->Palette); $i++)
			$PieChart->setSliceColor($i,$this->Palette[$i]);
				
		
		/* Draw an AA pie chart */ 
		$PieChart->draw2DPie(250,230,array("Radius"=>130,"DrawLabels"=>TRUE,"LabelStacked"=>TRUE,"Border"=>TRUE, "WriteValues"=>PIE_VALUE_NATURAL,"ValuePosition"=>PIE_VALUE_INSIDE));
		
		/* Write the legend box */ 
		$PieChart->drawPieLegend(550,100,array("Alpha"=>20));
		
		/* Render the picture (choose the best way) */
		$this->myPicture->autoOutput($this->path);	
	}	
}
?>