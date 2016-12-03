<?php
/**
 * @property DBContext $dbc
 * @author Qikang
 *
 */
class PaperLogic extends CI_Model {
	public function __construct() {
		parent::__construct ();
	}
	
	private $paperrule=array(
			"chapter"=>"all",
			"selcount1"=>20,
			"selscore1"=>2,
			"boolcount1"=>20,
			"boolscore1"=>2,
			"selcount2"=>3,
			"selscore2"=>3,
			"boolcount2"=>3,
			"boolscore2"=>3
			
	);
	
	private $group;
	private $qstlist;
	
	public function GeneratePaper($asid,$rule=null)
	{
		$this->group=array();
		$this->qstlist=array();
		
		//
		//build rule
		if($rule!=null)
		{
			array_replace($this->paperrule, $rule);
		}
		$rule=$this->paperrule;
		
		$this->load->model("Rep/DBContext","dbc");
		
		$this->load->model("Rep/AnswerSheet","asheet");
		$this->load->model("Rep/ExamChapter","echap");
		$asheet=$this->asheet->GetByID($asid);
		$chs=$this->echap->GetList(array("exam"=>$asheet->exam));
		$chaps=array();
		$chapnum=0;
		foreach ($chs as $c)
		{
			array_push($chaps, $c->chapter);
			$chapnum++;
		}
		//echo $chapnum;
		//get chapter number
// 		if($rule["chapter"]=="all")
// 		{
// 			$this->load->model("Rep/Chapter","chapter");
// 			$chapnum=$this->chapter->Count();
// 			$chaps=$this->chapter->AllIds();
// 		}
// 		else {
// 			$chapnum=count($rule["chapter"]);
// 			$chaps=$rule["chapter"];
// 		}
		
		//begin generate sel1
		$chapsel1count=$this->qstdistribute($chaps, $rule["selcount1"]);
		$chapsel2count=$this->qstdistribute($chaps, $rule["selcount2"]);
		$chapbool1count=$this->qstdistribute($chaps, $rule["boolcount1"]);
		$chapbool2count=$this->qstdistribute($chaps, $rule["boolcount2"]);
		
		$this->makeqstlist($chapsel1count, 0, 1, $rule["selscore1"]);
		$this->makeqstlist($chapsel2count, 1, 1, $rule["selscore2"]);
		$this->makeqstlist($chapbool1count, 0, 2, $rule["boolscore1"]);
		$this->makeqstlist($chapbool2count, 1, 2, $rule["boolscore2"]);
// 		$this->load->model("Rep/Paper","paper");
// 		$paper=$this->paper->Create(array(
// 			"papername"=>$papername
// 		));
		//$this->load->model("Rep/AnswerSheet","asheet");
		//print_r($paper);
		
		//$pid=$paper["idpaper"];
		$this->load->model("Rep/PaperQuestion","pq");
		foreach ($this->qstlist as $k=>$v)
		{
			$this->pq->Create(array(
					"paper"=>$asid,
					"question"=>$k,
					"score"=>$v,
                    
			));
		}

	}
	
	protected function qstdistribute($chaps,$allcount)
	{
		$out=array();
		//equal distribute
		$chapnum=count($chaps);
		if($chapnum<=$allcount)
		{
			$basecount=floor($allcount/$chapnum);
			$randcount=$allcount%$chapnum;
			//echo "bk".$basecount;
			if($randcount==0)
				$randkey=null;
			else
				$randkey=array_rand($chaps,$randcount);
			
			foreach ($chaps as $c)
			{
				$out[$c]=$basecount;
				
			}
			if($randkey!=null)
			foreach ($randkey as $k)
			{
				$out[$chaps[$k]]+=1;
			}
		}
		else {
			$randkey=array_rand($chaps,$allcount);
			foreach ($randkey as $k)
				$out[$chaps[$k]]=1;
		}
		return $out;
	}
	protected function makeqstlist($chapcount,$star,$type,$score)
	{
		
		$this->db->where("star",$star);
		$this->db->where_in("chapter",array_keys($chapcount));
		$this->db->where("type",$type);
		$this->db->order_by("","RANDOM");
		$this->db->select("idquestion,chapter,group");
		$rst=$this->db->get("question");
		//print_r($rst);
		foreach ($rst->result_object() as $row)
		{
			//print_r($row);
			//echo $row->chapter;
			if($chapcount[(int)($row->chapter)]>0){
				if(!empty($row->group))
				if(array_key_exists($row->group,$this->group))
					continue;
				else
					array_push($this->group, $row->group);
				$this->qstlist[$row->idquestion]=$score;
				$chapcount[$row->chapter]--;
			}
			if(array_sum($chapcount)==0)
				break;
		}
	}
	
	
	
}
?>