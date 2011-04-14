<?php

//function used to prettify output of arrays
//credit: http://www.terrawebdesign.com/multidimensional.php
//require('prettify_array.php');
if($_POST['details']==1)
  $show_detailed_work = true; //should we output the intermediate steps?
else
  $show_detailed_work = false;
$frequency = 0;
$num_in_1 = 0; //# pairs in burst pair sequence 1
$num_in_2 = 0; //# pairs in burst pair sequence 2
$bp_array[][] = array();
$current_index = 0;
$first_stored = false;

function prettify($array, $title, $one, $two)
{
  echo "<b>". $title. ":</b> <br>";
  echo "<table>\n<tr><td>".$one."</td><td>".$two."</td></tr>";
  $i = 0;
  while($i<count($array))
  {
    echo "<tr><td>".$array[$i][0]."</td><td>".$array[$i][1]."</td></tr>";
    $i++;
  }
  echo "</table>";
}

function prettify_h($array, $title,$one, $two)
{
  echo "<b>". $title. ":</b> <br>";
  echo "<table>\n<tr><td>".$one."</td><td>".$two."</td></tr>";
  $i = 0;
  while($i<count($array))
  {
    echo "<tr><td>".$i."</td><td>".$array[$i]."</td></tr>";
    $i++;
  }
  echo "</table>";
}

function prettify_s($string, $title)
{
  echo "<b>". $title . ": </b><br>";
  echo $string;
  echo "</br>";
}

//converts from hexadecimal to decimal
function convert($value)
{
  return base_convert($value, 16, 10);
}

function change_index($index)
{
  global $current_index;
  $current_index = $index;
}
function get_index()
{
  global $current_index;
  return $current_index;
}


//obtains all the on off pairs in dec format
function store_val($dec)
{
  $i = get_index();
  global $first_stored;
  global $bp_array;

  if(!$first_stored):
  {
    $bp_array[$i][0] = $dec;
    $first_stored = true;
  }
  elseif($first_stored):
  {
    $bp_array[$i][1] = $dec;
    change_index($i+1);
    $first_stored = false;
  }
  else:
  {}
  endif;
}

//obtains unique pairs
function get_unique($array)
{
  return array_unique($array);
}

//

if ($_POST['check']=="1")
{


  $original = $_POST['hex_original'];
  $orig_spaced = preg_split('/ /', $original, -1);
  if($show_detailed_work)
    prettify_h($orig_spaced, "Original Input", "Word", "Pronto Hex");
  
  $next_is_freq = false;
  $next_is_num1 = false;
  $next_is_num2 = false; 
  $go_grab = false; // Are we past the preamble and ready to grab burst pairs?
  
  foreach ($orig_spaced as $key=>$value)
  {
    $dec = convert($value);
    //echo $dec. " ";

    if ($go_grab):
      store_val($dec);
    elseif($next_is_num1):
    {
      $num_in_1 = $dec;
      $next_is_num2 = true;
      $next_is_num1 = false;
    }
    elseif($next_is_num2):
    {
      $num_in_2 = $dec;
      $go_grab = true;  
    }       
    elseif($next_is_freq):
    {
      $frequency = 1000000/($dec * 0.241246); //units of seconds
      $next_is_num1 = true;
    }
    elseif($dec==0): //if sequence is preamble
    {
      $next_is_freq = true;
    }
    else: //  
    {
      $next_is_freq = false;
      $next_is_on = false;
      $next_is_off = false;
    }
    endif;
  }
  
  prettify_s($num_in_1, "# pairs in burst sequence 1");
  prettify_s($num_in_2, "# pairs in burst sequence 2");
  prettify_s(($num_in_1+$num_in_2), "#Pairs total");
  prettify_s(round($frequency, 0), "Frequency (Hz)");
  
  if($show_detailed_work)
    prettify($bp_array, "Pronto Hex --> Decimal", "On", "Off");

  //convert it to units of time that can be understood by tv-b-gone
  for($i = 0; $i< $current_index; $i++)
  {
    $bp_array[$i][0] = round($bp_array[$i][0]*(1/$frequency)*100000, 0);
    $bp_array[$i][1] = round($bp_array[$i][1]*(1/$frequency)*100000, 0);
  }
  if($show_detailed_work)
    prettify($bp_array, "Decimal --> Units of time (e-6)", "On", "Off");  

  $values = array();
  
  //do a md5 to get unique "on" and "off" pairs :D
  foreach($bp_array as $value)
    $unique_md5[md5(serialize($value))] = $value;
  
  //remove the md5 key and replace it with autoindexing
  $unique_keys = array_keys($unique_md5);
  for($i = 0; $i < count($unique_keys); $i++)
    $unique_map_final[$i] = $unique_md5[$unique_keys[$i]];

  prettify($unique_map_final, "Unique Burst Pair Keys:", "On", "Off");
  
  //represent all the pairs in terms of unique pairs
  //returns a string of binary numbers all meshed together
  $binary_list ="";
  for($i = 0; $i < count($bp_array); $i++)
    for($j = 0; $j < count($unique_map_final); $j ++)
    {
      if ($bp_array[$i] == $unique_map_final[$j])
      {
        $binary_list .= base_convert($j, 10, 2);
        break;
      }
    }
  if($show_detailed_work)
    prettify_s($binary_list, "Binary List");


  //pads the binary list so that it can divide evenly by multiples of 8
  for($i = 0; $i < strlen($binary_list)%8; $i++)
    $binary_list .= "0";
  if($show_detailed_work)
    prettify_s($binary_list, "Binary List, but padded with 0's");

  //split every 8 numbers of binary and put them into arrays
  //converts ever 8 numbers into a hexadecimal
  $final_hex = array();
  for($i = 0; $i < strlen($binary_list); $i += 8)
  {
    $final_hex[$i/8] = "0x".base_convert(substr($binary_list, $i, 8), 2, 16);
  }
  
  
  prettify_h($final_hex, "Binary 8 bit groups --> Hexadecimal", "Index", "Hexadecimal");
  

  
  
  
  echo "<br>";

} else
echo "<html><body>";

?>
<form action="conver.php" method="post">
<textarea name="hex_original"></textarea>
<input type="hidden" name="check" value="1">
Should I show work?<input type="radio" name="details" value="1">
<input type="submit" value="convert!">
</form>
</body>
</html>

