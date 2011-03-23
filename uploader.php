<?php
class uploader{

  #Upload a file to a particular directory, or the current directory by default
  #A file of the same name will be overwritten
  #Arg1: A file object, likely from $_FILES["file"]
  #Arg2: Optional destination path
  public static function upload($fileObj, $destinationFolder = '.'){

    #Ensure file is the correct type: .csv
    if (strlen($fileObj['name']) > 4 and #has enough characters for .csv at the end
    substr_compare(strtolower($fileObj["name"]), ".csv", -4, 4) == 0){ #ends in .csv

      #If it has errors, stop and return the errors
      if ($fileObj["error"] > 0){
        return "Return Error Code: " . $fileObj["error"] . "<br />";
      }else{

        #check to see if the file exists,
        #to return a warning that it has been overwritten
        $fileExisted = false;
        if (file_exists($destinationFolder .'/'. $fileObj["name"])){
          $fileExisted = true;
        }

        #Without errors, move the uploaded file into the current directory
        move_uploaded_file($fileObj["tmp_name"],
        $destinationFolder .'/'. $fileObj["name"]);

        #Return messages of success or overwrite
        if($fileExisted){
          return "Overwrite was successful";
        }else{
          return "Upload was successful";
        }
      }
    }else{
      return "Please upload a csv file, not another type";
    }
  }
}