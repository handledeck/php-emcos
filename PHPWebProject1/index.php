<?php
$em=new emcos();
$exp= $em->connect();
$em->fill();
var_dump($em->tree);
$z=0;
class emcos{

    private $db_username;
    private $db_password;
    private $db_host;
    private $service;
    private $tns;
    private $conn=null;
    public  $tree;

    function __construct(string $host="10.208.4.83",string $service="emcor",string $username="emcos",string $password="emc05",int $id=49){
        $this->db_username=$username;
        $this->db_password=$password;
        $this->db_host=$host;
        $this->tns="(DESCRIPTION =(ADDRESS_LIST=(ADDRESS = (PROTOCOL = TCP)(HOST=".$host.")(PORT = 1521)))(CONNECT_DATA =(SERVICE_NAME=".$service.")))";
        $this->tree=new emc("root",$id);
    }

    function fill(){
        $this->folers($this->tree);
    }

     function connect():bool{
        try{
            $this->conn = new PDO("oci:dbname=".$this->tns.";charset=UTF8",$this->db_username,$this->db_password);

        }
        catch(Exception $exp){
            return false;
        }
        return true;
    }

    public function run(){
        $this->folers($this->tree);
    }

    private function folers(emc $e){
            $query=$this->conn->prepare("SELECT CONT_ID, COMP_ID, COMP_NAME FROM ST_GRC WHERE (CONT_ID='".$e->id."')");
            $query->execute();
            for($i=0; $row = $query->fetch(); $i++){
                $locemc=new emc($row['COMP_NAME'],$row['COMP_ID']);

                $e->add($locemc);
                $this->items($locemc);
                $this->folers($locemc);
            }

      }
    private function items(emc $emc){
        $query=$this->conn->prepare("SELECT GR_ID, POINT_ID, GR_NAME,POINT_CODE,POINT_NAME FROM ST_GRP WHERE (GR_ID = '".$emc->id."')");
        $query->execute();
        for($i=0; $row = $query->fetch(); $i++){
            $locit=new item($row['GR_ID'],$row['POINT_ID'],$row['GR_NAME'],$row['POINT_CODE'],$row['POINT_NAME']);
            $emc->add_item($locit);
        }
    }
}

class item{
       public $GR_ID;
       public $POINT_ID;
       public $GR_NAME;
       public $POINT_CODE;
       public $POINT_NAME;

       function __construct(int $gr_id,int $point_id, string $gr_name,int $point_code,string $point_name){
           $this->GR_ID=$gr_id;
           $this->POINT_ID=$point_id;
           $this->GR_NAME=$gr_name;
           $this->POINT_CODE=$point_code;
           $this->POINT_NAME=$point_name;
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


?>