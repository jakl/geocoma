<?php
class fileLister{

  #List files, given a specific extension, as form POST inputs as well as downloadable links
  #Args:
  #Post Parameter: Name of the POST parameter set on submission
  #Extension to limit the files displayed
  #Form Input Type: Either radio or checkbox
  #Directory: The directory to list files from
  public static function listFiles($formInputType = '', $extension = '.csv', $directory = '.'){
    $outputHTML = "";
    $directory .= '/';
    $extensionLength = strlen($extension);#Extension length is csv as well as the period

    $count = 1;
    $dir_handle = opendir($directory);
    #Cycle and capture all local files, and add link
    while ($file = readdir($dir_handle)) {
      
      #file name has to be at least longer than the extension
      #and has to have the correct extension
      if (strlen($file) > $extensionLength and
      substr_compare(strtolower($file), $extension, -$extensionLength, $extensionLength) == 0) {
        
        //Insert Spaces after 4 form entries
        if(3 == $count%4){
          //$outputHTML .= "<br />";
        }

        if($formInputType != ''){
          $outputHTML .= "<input type='$formInputType'
           name='file' value='".$directory.$file."' /><a href=".$directory.$file.">".$file."</a>" ;
        }else{
          $outputHTML .= "<a href=".$directory.$file.">".$file."</a>" ;
        }
      }
      $count++;
    }
    closedir($dir_handle);

    return $outputHTML;
  }
}