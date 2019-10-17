<?php

class Cache 
{
    var $filename;
       
    /**********************/
    function Cache()
    {
        $this-> cachedir = "/tmp";
    }
    /**********************/
    function save($filename,$value)
    {
	if( !file_exists($this-> cachedir)){ 
		mkdir($this-> cachedir);
		chmod($this-> cachedir, 0777);
	}
	$subdir=sprintf("%s/%s",$this-> cachedir,substr(md5($filename),0,2));
	if( !file_exists($subdir)){
	 	mkdir($subdir);
		chmod($subdir, 0777);
	}
	$filename = sprintf("%s/%s",$subdir,$filename);
	
        if($f = @fopen($filename,"wb"))
        {
            if(@fwrite($f,serialize($value)))
            {
                @fclose($f);
            }
            else die("Could not write to file ".filename." at Persistant::save");
        }
        else die("Could not open file ".$filename." for writing, at Persistant::save");
       
    }
    /**********************/
    function load($filename)
    {
	$vars=array();
	$subdir=sprintf("%s/%s",$this-> cachedir,substr(md5($filename),0,2));
	$filename = sprintf("%s/%s",$subdir,$filename);
        if(file_exists($filename)){ ;
        	$vars = unserialize(file_get_contents($filename));
	}
	return $vars;
    }
    /**********************/
   function check($filename)
   {
	$subdir=sprintf("%s/%s",$this-> cachedir,substr(md5($filename),0,2));
	$filename = sprintf("%s/%s",$subdir,$filename);
	return file_exists($filename);
   }
}



?>
