<?php
require_once("common/db.class.php");

class calendar
{
	protected $db;
	protected $nbcolumns;
	protected $offset;
	protected $innerclass;
	protected $tableclass;
	protected $fixedcolumn;
	
	function __construct($nbcolumns = 10, $offset = 0)
	{
		$this->nbcolumns = $nbcolumns;
		$this->offset = $offset;
		$this->innerclass = "inner";
		$this->outerclass = "outer";
		$this->tableclass = "table";
		$this->fixedcolumn = false;

		$this->db = new db();
		if ($this->db->select_db("calendar") == false)
		{
			error_log("create calendar DB");
			$this->db->create_db("calendar");
		}
		$sql="SHOW TABLES  FROM `calendar` LIKE 'ints'";
		$result = $this->db->query($sql);
		
		if ($result === false || $result->num_rows() === 0)
		{
			$sql = "CREATE TABLE `ints` (i INTEGER);";
			$this->db->query($sql);
			$sql = "INSERT INTO `ints` VALUES (0), (1), (2), (3), (4), (5), (6), (7), (8), (9);";
			$this->db->query($sql);
		}
	}
	function fixedcolumn()
	{
		$this->fixedcolumn = true;
	}
	function bootstrap()
	{
		$this->innerclass .= " table-responsive";
		$this->tableclass .= " table-striped";
	}
	private function _generateheader($tab, $rowspan, $title)
	{
		if ($this->fixedcolumn)
		{
?>
			<th class="fixed-column text-center" rowspan="<?=$rowspan?>"><span><?=$title?></span>
<?php
			if ($this->offset > 0 )
			{
?>
				<a role="button" href="?tab=<?=$tab?>&offset=<?=$this->offset-$this->nbcolumns?>" class="pull-right">
					<span class="glyphicon glyphicon-circle-arrow-right"><span class="sr-only"><?=_("Next")?></span></span>
				</a>
<?php
			}
?>
				<a role="button" href="?tab=<?=$tab?>&offset=<?=$this->offset+$this->nbcolumns?>" class="pull-right">
					<span class="glyphicon glyphicon-circle-arrow-left"><span class="sr-only"><?=_("Previous")?></span></span>
				</a>
			</th>
<?php
		}
	}
	private function _generatemonths($colwidth, $title)
	{
		$sql="SELECT @Date:=(CURDATE() - INTERVAL (u.i*10 + v.i + ".$this->offset.") MONTH) AS Date, YEAR(@Date) AS Year, MONTHNAME(@Date) AS Month FROM ints AS u JOIN ints AS v WHERE ( u.i*10 + v.i ) < ".$this->nbcolumns." ORDER BY Date";
		$result = $this->db->query($sql);
		$gridrow="";
?>
		<tr>
<?php
		$this->_generateheader("months", 2, $title);
		for ($i = 0; $i < $result->num_rows(); $i++)
		{
			$value = $result->fetch_array();
			list($year,$month,$day) = explode("-",$value["Date"]);
?>
			<th id="<?=$year."-".$month?>" class="small text-center" style="width:<?=$colwidth?>"><?=_($value["Month"])?> <?=_($value["Year"])?></th>
<?php
			$gridrow.="<td data-month='".$year."-".$month."' class='text-center' headers='".$year."-".$week."'></td>";
		}
?>
		</tr>
<?php
		return $gridrow;
	}
	private function _generatedays($colwidth, $title)
	{
		$sql="SELECT @Date:=(CURDATE() - INTERVAL (u.i*10 + v.i + ".$this->offset.") DAY) AS Date, YEAR(@Date) AS Year, MONTHNAME(@Date) AS Month, DAYOFMONTH(@Date) AS Day, WEEKDAY(@Date) AS WeekDay FROM ints AS u JOIN ints AS v WHERE ( u.i*10 + v.i ) < ".$this->nbcolumns." ORDER BY Date";
		$result = $this->db->query($sql);
		$gridrow="";
		$weekclass = ["","","","","","bg-info","bg-info"];

		$dates = array();
		$year = array();
		$month = array();
		$day = array();
		for ($i = 0; $i < $result->num_rows(); $i++)
		{
			$value = $result->fetch_array();
			$dates[] = $value["Date"];
			$year[] = $value["Year"];
			$month[] = $value["Month"];
			$day[] = $value["Day"];
			$weekdayclass[] = $weekclass[$value["WeekDay"]];
			$gridrow.="<td data-date='".$value["Date"]."' class='text-center ".$value["WeekDay"]."' headers='".$value["Date"]."'></td>";
		}
?>
		<tr>
<?php
		$this->_generateheader("days", 1, $title);
		for ($i = 0; $i < $result->num_rows(); $i++)
		{
			$colspan++;
?>
			<th id="<?=$dates[$i]?>" class="small text-center <?=$weekdayclass[$i]?>" style="width:<?=$colwidth?>"><?=$day[$i]?> <?=_($month[$i])?></th>
<?php
		}
?>
		</tr>
<?php
		return $gridrow;
	}
	private function _generateweeks($colwidth)
	{
		$sql="SELECT @Date:=(CURDATE() - INTERVAL (u.i*10 + v.i + ".$this->offset.") WEEK) AS Date, WEEK(@Date,3) AS Week, YEAR(@Date) AS Year FROM ints AS u JOIN ints AS v WHERE ( u.i*10 + v.i ) < ".$this->nbcolumns." ORDER BY Date;";
		$result = $this->db->query($sql);
		$gridrow="";
?>
		<tr>
<?php
		if (!isset($title))
			$title="";
		$this->_generateheader("weeks", 2, $title);
		for ($i = 0; $i < $result->num_rows(); $i++)
		{
			$value = $result->fetch_array();
?>
			<th id="<?=$value["Year"]."-".$value["Week"]?>" class="small text-center" style="width:<?=$colwidth?>"><span><?=$value["Year"]?> w<?=$value["Week"]?></span></th>
<?php
			$gridrow.="<td data-week='".$value["Year"]."-".$value["Week"]."' class='text-center' headers='".$value["Year"]."-".$value["Week"]."'></td>";
		}
		return $gridrow;
	}

	function generateTable($tab="weeks", $id="", $title = "&nbsp;")
	{
		$this->id = $id;
		$rowspan = 2;
?>
		<div class="<?=$this->outerclass?>">
			<div class="<?=$this->innerclass?>">
				<table class="<?=$this->tableclass?>">
					<thead>
<?php
		$colwidth = 100 / $this->nbcolumns;
		$colwidth = "".$colwidth."%";
		$colwidth = "auto";
		switch ($tab)
		{
			case "months":
				$this->gridrow = $this->_generatemonths($colwidth, $title);
			break;
			case "days":
				$this->gridrow = $this->_generatedays($colwidth, $title);
			break;
			case "weeks":
			default:
					$this->gridrow = $this->_generateweeks($colwidth, $title);
			break;
		}
?>
				</tr>
			</thead>
			<tbody id="<?=$id?>">
			</tbody>
		</table> 
	</div>
</div>
<?php
	}
	function generateRow($id="", $class="", $title="")
	{
		$row = "<tr id='".$id."' class='".$class."'>";
		if ($this->fixedcolumn)
			$row .= "<th scope='row' class='fixed-column'>".$title."</th>";
		$row .= $this->gridrow;
		$row .= "</tr>";
		return $row;
	}
};
?>
