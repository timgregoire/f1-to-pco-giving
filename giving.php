<?php

//PCO Personal Access Token info
$AppID = "";
$secret = "";

$current_endpoint = 'https://api.planningcenteronline.com/giving/v2/batches/1/donations';
$create_batch_endpoint = 'https://api.planningcenteronline.com/giving/v2/batches';

ini_set('memory_limit', '256M');

$gifts = array_map('str_getcsv', file('donations.csv'));
$people = array_map('str_getcsv', file('pepole.csv'));


/*
These lists are where the script expects to see certain data in its input CSVs



People Data Structure
[0] = F1 Indiv ID
[5] = Gender
[6] = Child (bool)
[7] = Marital Status
[11] = Household name
[12] = F1 Household ID
[13] = PCO ID


Giving Record Data Structure
[6] = Person/household ID to match
[20] = Fund
[31] = Received date
[36] = type
[38] = Bank card type
[40] = reference as check number, filler if not
[42] = amount
*/

//Create batch for current month
$batch_description = "January 2016";
$previous_month = "January 2016";

foreach ($gifts as $donation)
  {

    //Get first month
    $received_date = date("Y-m-d", strtotime($donation[31]));
    $current_month = substr($received_date,5,2);

    if($current_month == "01")
      $current_month = "January";
    if($current_month == "02")
      $current_month = "February";
    if($current_month == "03")
      $current_month = "March";
    if($current_month == "04")
      $current_month = "April";
    if($current_month == "05")
      $current_month = "May";
    if($current_month == "06")
      $current_month = "June";
    if($current_month == "07")
      $current_month = "July";
    if($current_month == "08")
      $current_month = "August";
    if($current_month == "09")
      $current_month = "September";
    if($current_month == "10")
      $current_month = "October";
    if($current_month == "11")
      $current_month = "November";
    if($current_month == "12")
      $current_month = "December";

    $current_month .= " " . substr($received_date,0,4);
    //var_dump($current_month);
    $batch_description = $current_month;


if($current_month != "January 1970")
{
    if($current_month != $previous_month)
      {

          $batch_request_object = create_batch_request_object($batch_description);
          $opts = create_opts($batch_request_object,$AppID,$secret);
          $context = stream_context_create($opts);
          $create_batch_result = file_get_contents($create_batch_endpoint, false, $context);
          $create_batch_result = json_decode($create_batch_result , true);
          $current_endpoint = $create_batch_result["data"]["links"]["donations"];
          var_dump($current_endpoint);
          $previous_month = $current_month;

      }
}

//End create batch code





//Begin person matching code
    $pco_person_id = NULL;


      //IDs match directly to a single person
      foreach($people as $person)
        {
          if($person[0] == $donation[6])
            {
              $pco_person_id = $person[13];
            }
        }

      //Else, find the first household that the transaction ID matches
      if($pco_person_id == NULL)
      {
        foreach($people as $person)
          {
            if($pco_person_id == NULL)
              {
                if($person[12] == $donation[6])
                {
                  $pco_person_id = $person[13];
                }
              }


          }
      }

      //if none found, print out error message
      if($pco_person_id == NULL)
        {
          echo "person not found";
          var_dump($donation[0]);
        }


//End person matching code




        //Translate F1 Fund to PCO Fund ID
        if($donation[20] == "Georgia Barnett")
          $donation_fund = 44030;
        if($donation[20] == "General Fund")
          $donation_fund = 42451;
        if($donation[20] == "Building Fund")
          $donation_fund = 43113;
        if($donation[20] == "Mission Trip")
          $donation_fund = 43115;
        if($donation[20] == "Annie Armstrong")
          $donation_fund = 43114;
        if($donation[20] == "Lottie Moon")
          $donation_fund = 44029;


        //Strip extra characters and format amount of donation for PCO
        $donation_amount = substr($donation[42], 1);
        $donation_amount = str_replace( ',', '',$donation_amount);
        $donation_amount  = (float)$donation_amount * 100;


        //Create the request object for the type of donation we want to record
        if($donation[36] == "Cash")
          {
          //  echo "cash";
            $donation_request_object = create_cash_request_object($received_date,$pco_person_id,$donation_amount,$donation_fund);
          }

        if($donation[36] == "ACH")
          {
            //echo "ach";
            $donation_request_object = create_ach_request_object($received_date,$pco_person_id,$donation_amount,$donation_fund);
          }

        if($donation[36] == "Check")
          {
            //echo "check";
            $check_number = $donation[40];
            if($donation[40] == NULL)
              {
                $check_number = "00000";
              }


            $donation_request_object = create_check_request_object("Null",$check_number,$received_date,$pco_person_id,$donation_amount,$donation_fund);
          }

        if($donation[36] == "Credit Card")
          {
            //echo "card";
            $donation_request_object = create_card_request_object("Null",$received_date,$pco_person_id,$donation_amount,$donation_fund);
          }




    //post the donation
    if($pco_person_id != NULL)
      {
        var_dump($donation_request_object);
        $opts = create_opts($donation_request_object,$AppID,$secret);
        $context = stream_context_create($opts);
        $person_file = file_get_contents($current_endpoint, false, $context);
      }



    if($pco_person_id == NULL)
      {
        var_dump($donation_request_object);
      }



    $donation_fund = NULL;
    $donation_date = NULL;
    $donation_amount = NULL;
    $check_number = NULL;
    $donation_request_object = NULL;
    $pco_person_id = NULL;



  }




function create_opts($request_object, $AppID, $secret){

  $opts = array(
    'http'=>array(
      'method'=>"POST",
      'header' => "Content-Type: application/x-www-form-urlencoded\r\n" . "Authorization: Basic " . base64_encode("$AppID:$secret"),
       'content'=>$request_object
      )
     );
  return $opts;
}

function create_cash_request_object($received_date,$person_pco_id,$donation_amount,$donation_fund){
  $request_object =json_encode(
    array(
      'data'=>array(
      'type'=>'Donation',
      'attributes'=>array(
        "payment_method"=>"cash",
        "received_at"=> $received_date),
        "relationships"=>array(
        "person" => array(
          "data"=>array(
          "type"=>"Person",
          "id" => (int)$person_pco_id
        )
      ),
        "payment_source"=>array(
          "data"=>array(
            "type"=>"PaymentSource",
            "id"=>'525'
          )
        )
      )
    ),
      "included"=> array(
        array(
          "type"=>"Designation",
          "attributes"=>array(
            "amount_cents" => $donation_amount
          ),
          "relationships"=>array(
            "fund"=>array(
              "data"=>array(
                "type"=>"Fund",
                "id"=> $donation_fund
              )
            )
          )
        )
      )
  )  );

  return $request_object;
  }

function create_check_request_object($bank_name,$check_number,$donation_date,$person_pco_id,$donation_amount,$donation_fund){
  $request_object =json_encode(
    array(
      'data'=>array(
      'type'=>'Donation',
      'attributes'=>array(
        "payment_method"=>"check",
        "payment_brand"=>$bank_name,
        "payment_check_number"=>$check_number,
        "payment_check_dated_at"=>$donation_date,
        "received_at"=> $donation_date),
        "relationships"=>array(
        "person" => array(
          "data"=>array(
          "type"=>"Person",
          "id" => $person_pco_id
        )
      ),
        "payment_source"=>array(
          "data"=>array(
            "type"=>"PaymentSource",
            "id"=>'525'
          )
        )
      )
    ),
      "included"=> array(
        array(
          "type"=>"Designation",
          "attributes"=>array(
            "amount_cents" => $donation_amount
          ),
          "relationships"=>array(
            "fund"=>array(
              "data"=>array(
                "type"=>"Fund",
                "id"=> $donation_fund
              )
            )
          )
        )
      )
    )  );

  return $request_object;
}

function create_card_request_object($card_brand,$donation_date,$person_pco_id,$donation_amount,$donation_fund){
  $request_object =json_encode(
    array(
      'data'=>array(
      'type'=>'Donation',
      'attributes'=>array(
        "payment_method"=>"card",
        "payment_brand"=>$card_brand,
        "payment_method_sub"=>"credit",
        "received_at"=> $donation_date),
        "relationships"=>array(
        "person" => array(
          "data"=>array(
          "type"=>"Person",
          "id" => (int)$person_pco_id
        )
      ),
        "payment_source"=>array(
          "data"=>array(
            "type"=>"PaymentSource",
            "id"=>'525'
          )
        )
      )
    ),
      "included"=> array(
        array(
          "type"=>"Designation",
          "attributes"=>array(
            "amount_cents" => $donation_amount
          ),
          "relationships"=>array(
            "fund"=>array(
              "data"=>array(
                "type"=>"Fund",
                "id"=> $donation_fund
              )
            )
          )
        )
      )
  )  );

  return $request_object;
}

function create_ach_request_object($donation_date,$pco_person_id,$donation_amount,$donation_fund){
  $request_object =json_encode(
    array(
      'data'=>array(
      'type'=>'Donation',
      'attributes'=>array(
        "payment_method"=>"ach",
        "received_at"=> $donation_date),
        "relationships"=>array(
        "person" => array(
          "data"=>array(
          "type"=>"Person",
          "id" => (int)$pco_person_id
        )
      ),
        "payment_source"=>array(
          "data"=>array(
            "type"=>"PaymentSource",
            "id"=>'525'
          )
        )
      )
    ),
      "included"=> array(
        array(
          "type"=>"Designation",
          "attributes"=>array(
            "amount_cents" => $donation_amount
          ),
          "relationships"=>array(
            "fund"=>array(
              "data"=>array(
                "type"=>"Fund",
                "id"=> $donation_fund
              )
            )
          )
        )
      )
  )  );

    return $request_object;
  }

function create_batch_request_object($batch_description){
    $request_object = json_encode(
      array(
        "data"=>array(
          "type"=>"Batch",
          "attributes"=>array(
            "description"=>$batch_description
          )
        )
        )
      );
  return $request_object;


  }




?>
