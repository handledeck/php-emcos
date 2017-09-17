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
                $this->folers($locemc);
            }

      }
}


class emc
{
    public $id;
    public $name;
    public $sub;

    function __construct(string $name,int $id){
        $this->name=$name;
        $this->sub=array();
        $this->id=$id;
    }

    function add(emc $obj){
        $obj->parent=$this->id;
        array_push($this->sub,$obj);
    }
}


?>