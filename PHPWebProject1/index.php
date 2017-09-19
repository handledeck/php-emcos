<?php
//git branch edit
$em=new emcos();
$exp= $em->connect();
//$em->fill();
//var_dump($em->tree);
$z=0;
class emcos{

    private $db_username;
    private $db_password;
    private $db_host;
    private $service;
    private $tns;
    private $conn=null;
    public  $tree;

    function __construct(string $host="10.208.4.83",string $service="emcor",string $username="sau",string $password="sau",int $id=49){
        $this->db_username=$username;
        $this->db_password=$password;
        $this->db_host=$host;
        $this->service=$service;
        $this->tns="(DESCRIPTION =(ADDRESS_LIST=(ADDRESS = (PROTOCOL = TCP)(HOST=".$host.")(PORT = 1521)))(CONNECT_DATA =(SERVICE_NAME=".$service.")))";
        $this->tree=new emc("root",$id);
    }



     function connect():bool{
        try{
            $this->conn = new PDO("oci:dbname=".$this->tns.";charset=UTF8",$this->db_username,$this->db_password);
            $this->get_folders($this->tree);
            return true;
        }
        catch(Exception $exp){
            return false;
        }

    }


    private function get_folders(emc $e){
            $query=$this->conn->prepare("SELECT CONT_ID, COMP_ID, COMP_NAME FROM ST_GRC WHERE (CONT_ID='".$e->id."')");
            $query->execute();
            for($i=0; $row = $query->fetch(); $i++){
                $locemc=new emc($row['COMP_NAME'],$row['COMP_ID']);

                $e->add($locemc);
                //$this->get_items($locemc);
                $this->get_folders($locemc);
            }
      }

    private function get_items(emc $emc){
        $query=$this->conn->prepare("SELECT GR_ID, POINT_ID, GR_NAME,POINT_CODE,POINT_NAME FROM ST_GRP WHERE (GR_ID = '".$emc->id."')");
        $query->execute();
        for($i=0; $row = $query->fetch(); $i++){
            $locit=new item($row['GR_ID'],$row['POINT_ID'],$row['GR_NAME'],$row['POINT_CODE'],$row['POINT_NAME']);
          // $this->item_schedule($locit);
            $emc->add_item($locit);
            //$this->item_data($locit,"2017.09.19","2017.09.19");
        }
    }

    private function get_item_schedule(item $item){
        $query=$this->conn->prepare("SELECT ML_NAME, ML_ID, PL_ID, POINT_ID,AGGS_TYPE_NAME,AGGS_TYPE_ID,EU_CODE,MD_NAME,MD_ID FROM ST_PL WHERE (POINT_ID = '".$item->POINT_ID."') and (MD_ID=5 OR MD_ID=3 OR MD_ID=6 OR MD_ID=8) ORDER BY ML_ID");
        $query->execute();
        for($i=0; $row = $query->fetch(); $i++){
            $sch=new Schedule($row['ML_NAME'],$row['ML_ID'],$row['PL_ID'],$row['POINT_ID'],$row['AGGS_TYPE_NAME'],$row['AGGS_TYPE_ID'],$row['EU_CODE'],$row['MD_NAME'],$row['MD_ID']);
            $item->add_schedule($sch);

        }
    }
    ///format date time "2017.9.13"
    public function get_item_data(item $item,string $datefrom,string $dateto):array{
        $query=$this->conn->prepare("SELECT POINT_ID,ML_ID,ET I, VAL, nvl(PL_RV,VAL) DR,DSTATUS QUALITY".
                " from ST_AR where DA>=to_date('".$datefrom."','YYYY.MM.DD') and DA<=to_date('".$dateto."','YYYY.MM.DD') and POINT_ID=".$item->POINT_ID." and ML_ID=49 order by I");
        $query->execute();
        $emcos_datas=array();
        for($i=0; $row = $query->fetch(); $i++){
            $colemd=new EmcosData();
            $colemd->date=$row['I'];
            $colemd->value=$row['VAL'];
            $colemd->quality=$row['QUALITY'];
            $colemd->ml_id=$row['ML_ID'];
            $colemd->point_id=$row['POINT_ID'];
            array_push($emcos_datas,$colemd);
        }
        return $emcos_datas;
    }

}

class Schedule{
   public $ML_NAME;
   public $ML_ID;
   public $PL_ID;
   public $POINT_ID;
   public $AGGS_TYPE_NAME;
   public $AGGS_TYPE_ID;
   public $EU_CODE;
   public $MD_NAME;
   public $MD_ID;

   function __construct($ML_NAME,$ML_ID, $PL_ID,$POINT_ID,$AGGS_TYPE_NAME,$AGGS_TYPE_ID,$EU_CODE,$MD_NAME,$MD_ID){
   $this->ML_NAME=$ML_NAME;
   $this->ML_ID=$ML_ID;
   $this->PL_ID=$PL_ID;
   $this->POINT_ID=$POINT_ID;
   $this->AGGS_TYPE_NAME=$AGGS_TYPE_NAME;
   $this->AGGS_TYPE_ID=$AGGS_TYPE_ID;
   $this->EU_CODE=$EU_CODE;
   $this->MD_NAME=$MD_NAME;
   $this->MD_ID=$MD_ID;
   }
}

class item{
       public $GR_ID;
       public $POINT_ID;
       public $GR_NAME;
       public $POINT_CODE;
       public $POINT_NAME;
       public $SCHEDULE;

       function __construct(int $gr_id,int $point_id, string $gr_name,int $point_code,string $point_name){
           $this->GR_ID=$gr_id;
           $this->POINT_ID=$point_id;
           $this->GR_NAME=$gr_name;
           $this->POINT_CODE=$point_code;
           $this->POINT_NAME=$point_name;
           $this->SCHEDULE=array();
       }

       function add_schedule(Schedule $schedule){
           array_push($this->SCHEDULE,$schedule);
       }
}

class emc
{
    public $id;
    public $name;
    public $sub;
    public $items;

    function __construct(string $name,int $id){
        $this->name=$name;
        $this->sub=array();
        $this->id=$id;
        $this->items=array();
    }

    function add(emc $obj){
        $obj->parent=$this->id;
        array_push($this->sub,$obj);
    }

    function add_item(item $item){
        array_push($this->items,$item);
    }

   function __toString(){
       return $this->name;
   }
}

class EmcosData{
        public $date;
        public $point_id;
        public $ml_id;
        public $quality;
        public $value;
        public $dr;
}

?>