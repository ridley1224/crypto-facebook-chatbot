<?php

require_once('../../../Connections/altcoins.php');
include("functions.php");

date_default_timezone_set('America/Detroit');

//IBM Watson Conversation for AI configuration file

include("watson.php");

//Facebook Messenger API configuration file

include("fb.php");



$subscriberStatus = " AND ispreview = 1";
$isSubscriber = false;


//query current coins list

mysql_select_db($database_altcoins, $altcoins);
$query_rsSymbols = "SELECT * FROM coins WHERE 0 = 0" . $subscriberStatus;

$rsSymbols = mysql_query($query_rsSymbols, $altcoins) or die(mysql_error());
$row_rsSymbols = mysql_fetch_assoc($rsSymbols);
$totalRows_rsSymbols = mysql_num_rows($rsSymbols);

$symbols = array();

if($totalRows_rsSymbols)
{
	do {

		$symbols[] = $row_rsSymbols['Symbol'];

	}  while ($row_rsSymbols = mysql_fetch_assoc($rsSymbols));
}

//check for a postback from facebook when user presses a button in the chat

if (!empty($postback) && (in_array($postback,$symbols) || $postback == "fast" || $postback == "long" || $postback == "all" || $postback == "get_started" || strpos($postback, '_report') !== false)) {
		
	//if postback is in symbols array
	
	$answer = "";
		
	if(in_array($postback,$symbols))
	{						
		mysql_select_db($database_altcoins, $altcoins);
		$query_rsSymbolInfo = "SELECT a.*,b.* FROM (SELECT * from coins WHERE Symbol = '".$postback."') as a INNER JOIN (SELECT * from coinentry ) as b ON a.coinid = b.coinid ORDER BY b.entrydate desc LIMIT 1";

		$rsSymbolInfo = mysql_query($query_rsSymbolInfo, $altcoins) or die(mysql_error());
		$row_rsSymbolInfo = mysql_fetch_assoc($rsSymbolInfo);
		$totalRows_rsSymbolInfo = mysql_num_rows($rsSymbolInfo);

		if($totalRows_rsSymbolInfo)
		{
			do {
				
				$theSymbol = $row_rsSymbolInfo['Symbol'];
				
				$symbolInfo = "Name: " . $row_rsSymbolInfo['Name'] . chr(10) . "Symbol: " . $row_rsSymbolInfo['Symbol'] . chr(10) . "Current Price: $" . $row_rsSymbolInfo['Current Price'] . chr(10) . "Change 24hr: " . $row_rsSymbolInfo['Change (24H)'] . "% " . chr(10) . "Change 7 day: " . $row_rsSymbolInfo['Change (7D)'] . "%" . chr(10) . "Our Return on Investment: " . chr(10) . $row_rsSymbolInfo['ReturnInv'] . "% " . 📈 . chr(10) . "Buy/Hold/Sell: " . $row_rsSymbolInfo['Buy up Spread'] . chr(10) . "Buy Up To Price: $" . $row_rsSymbolInfo['Buy up to price'] . chr(10);	

			}  while ($row_rsSymbolInfo = mysql_fetch_assoc($rsSymbolInfo));
		}
		
		if($postback != "BTC") //no report for Bitcoin2
		{
			$answer = ["attachment"=>[
					  "type"=>"template",
					  "payload"=>[
						"template_type"=>"button",
						"text"=>$symbolInfo,
						"buttons"=>[
						  [
							"type"=>"postback",
							"title"=>"View Report",
							"payload"=>$theSymbol . "_report"
						  ]
						]
					  ]
			]];

			$response = [
				'recipient' => [ 'id' => $senderId ],
				'message' => $answer
			];
		}
		else
		{
			$response = [
				'recipient' => [ 'id' => $senderId ],
				'message' =>  [ 'text' => $symbolInfo]
			];
		}
	}
	else if (strpos($postback, '_report') !== false) //postback requesting report for specific coin
	{
		$theSymbol = explode("_", $postback);
				
		mysql_select_db($database_altcoins, $altcoins);
		$query_rsSymbolInfo = "SELECT report FROM coins WHERE Symbol = '".$theSymbol[0]."'";

		$rsSymbolInfo = mysql_query($query_rsSymbolInfo, $altcoins) or die(mysql_error());
		$row_rsSymbolInfo = mysql_fetch_assoc($rsSymbolInfo);
		$totalRows_rsSymbolInfo = mysql_num_rows($rsSymbolInfo);

		if($totalRows_rsSymbolInfo)
		{
			do {
				
				if($isSubscriber == false)
				{
					$answer = substr($row_rsSymbolInfo['report'], 0, 500) ."...". chr(10). chr(10) . "Please JOIN NOW to view full report";
				}
				else
				{
					//$answer = $row_rsSymbolInfo['report'];
					$answer = substr($row_rsSymbolInfo['report'], 0, 500) ."...". chr(10). chr(10) . "Please JOIN NOW to view full report";
				}

			}  while ($row_rsSymbolInfo = mysql_fetch_assoc($rsSymbolInfo));
		}
		
		$response = [
			'recipient' => [ 'id' => $senderId ],
			'message' =>  [ 'text' => $answer]
		];	
	}
	else if ($postback == "fast" || $postback == "long" || $postback == "all") //checking for request for fast-moving coins, long-term coins or all coins
	{
		if($postback == "fast")
		{
			if($isSubscriber == true)
			{
				$title = "Fast Movers:";
			}
			else
			{
				$title = "Fast Movers: (2 out of 17). Please JOIN NOW to view full list of our featured coins";
			}
			
			$term = 0;
		}
		else if($postback == "long")
		{
			if($isSubscriber == true)
			{
				$title = "Long Term:";
			}
			else
			{
				$title = "Long Term: (3 out of 19). Please JOIN NOW to view full list of our featured coins";
			}
			
			$term = 1;
		}
		else if($postback == "all")
		{
			if($isSubscriber == true)
			{
				$title = "Watch List Coins:";
			}
			else
			{
				$title = "Watch List Coins: (5 out of 36). Please JOIN NOW to view full list of our featured coins";
			}
			
			$term = 2;
		}
		
		mysql_select_db($database_altcoins, $altcoins);
		
		
		if($term == 2)
		{
			$query_rsTermSymbols = "SELECT * FROM coins WHERE 0=0 " . $subscriberStatus . " ORDER BY islongterm desc";
		}
		else
		{
			$query_rsTermSymbols = "SELECT * FROM coins WHERE 0=0 " . $subscriberStatus . " AND islongterm = " . $term;
		}
		
		$rsTermSymbols = mysql_query($query_rsTermSymbols, $altcoins) or die(mysql_error());
		$row_rsTermSymbols = mysql_fetch_assoc($rsTermSymbols);
		$totalRows_rsTermSymbols = mysql_num_rows($rsTermSymbols);
		
		if($postback == "all")
		{
			if($totalRows_rsTermSymbols)
			{
				$string = "Watch List Coins: " . chr(10) . chr(10);

				do {
					
					if($row_rsTermSymbols['islongterm'] == 1)
					{
						$string .=$row_rsTermSymbols['Name'] . " (" . $row_rsTermSymbols['Symbol'] . ") Long" . chr(10);
					}
					else
					{
						$string .=$row_rsTermSymbols['Name'] . " (" . $row_rsTermSymbols['Symbol'] . ") Fast" . chr(10);
					}
					

				}  while ($row_rsTermSymbols = mysql_fetch_assoc($rsTermSymbols));
			}
			
			//populates response to send back to facebook user

			$response = [
				'recipient' => [ 'id' => $senderId ],
				'message' =>  [ 'text' => $string]
			];
		}
		else
		{
			if($isSubscriber == true)
			{
				if($totalRows_rsTermSymbols)
				{
					$string = "Watch List Coins: " . chr(10) . chr(10);

					do {

						if($row_rsTermSymbols['islongterm'] == 1)
						{
							$string .=$row_rsTermSymbols['Name'] . " (" . $row_rsTermSymbols['Symbol'] . ") Long" . chr(10);
						}
						else
						{
							$string .=$row_rsTermSymbols['Name'] . " (" . $row_rsTermSymbols['Symbol'] . ") Fast" . chr(10);
						}


					}  while ($row_rsTermSymbols = mysql_fetch_assoc($rsTermSymbols));
				}
				
				//populates response to send back to facebook user

				$response = [
					'recipient' => [ 'id' => $senderId ],
					'message' =>  [ 'text' => $string]
				];
			}
			else
			{			
				$termsymbols = array();

				if($totalRows_rsTermSymbols)
				{
					do {

						$termsymbols[] = [

							"type"=>"postback",
							"title"=>$row_rsTermSymbols['Name'] . " (" . $row_rsTermSymbols['Symbol'] . ")",
							"payload"=>$row_rsTermSymbols['Symbol']
						  ];

					}  while ($row_rsTermSymbols = mysql_fetch_assoc($rsTermSymbols));
				}

				$answer = ["attachment"=>[
						  "type"=>"template",
						  "payload"=>[
							"template_type"=>"button",
							"text"=>$title . "." . chr(10). chr(10) . "Select a coin to view price details.",
							"buttons"=>$termsymbols
						  ]
				]];
				
				//populates response to send back to facebook user

				$response = [
					'recipient' => [ 'id' => $senderId ],
					'message' => $answer
				];			
			}			
		}
	}
	else if($postback == "get_started") // Checks for user pressing Get Started button. It's displayed to user when starting conversation.
	{
		$text = "Hello. I'm CryptoBot, and I'd like to help you with any of your questions on up and coming cryptocurrencies." . chr(10) . chr(10) . "Would you like to see a list of the fast mover alt coins or some long term investment coins?";
		
		$answer = ["attachment"=>[
				  "type"=>"template",
				  "payload"=>[
					"template_type"=>"button",
					"text"=>$text,
					"buttons"=>[
					  [
						"type"=>"postback",
						"title"=>"Long Term Coins",
						"payload"=>"long"
					  ],
					  [
						"type"=>"postback",
						"title"=>"Fast Mover Coins",
						"payload"=>"fast"
					  ],
					  [
						"type"=>"postback",
						"title"=>"Watch List Coins",
						"payload"=>"all"
					  ]
					]
				  ]
				  ]];
			
		$response = [
			'recipient' => [ 'id' => $senderId ],
			'message' => $answer
		];
	}
	
	//sends chatbot response back to facebook messenger user
			
	$ch = curl_init('https://graph.facebook.com/v2.6/me/messages?access_token='.$accessToken);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($response));
		curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
		
	if(!empty($input))
	{
		$result2 = curl_exec($ch);
		$isinput = 1;
	}
	else
	{
		$isinput = 0;
	}
	
	curl_close($ch);
	
	$date = date("Y-m-d H:i:s");
	
	//save postback data into our database for analytics
	
	$insertSQL = sprintf("INSERT INTO postbacks (entry, isthere, answer, response1, responseval, senderid,isinput,termsymbols,entrydate) VALUES (%s,%s,%s,%s,%s,%s,%s,%s,%s)",
					GetSQLValueString(mysql_real_escape_string($postback), "text"),
					GetSQLValueString(in_array($postback,$symbols), "int"),
					GetSQLValueString(mysql_real_escape_string($answer), "text"),
					GetSQLValueString(isset($response), "int"),
					GetSQLValueString(print_r($response,true), "text"),
					GetSQLValueString(mysql_real_escape_string($senderId), "text"),
					GetSQLValueString($isinput, "int"),
					GetSQLValueString(print_r($termsymbols,true), "text"),
					GetSQLValueString($date, "date"));
	
	mysql_select_db($database_altcoins, $altcoins);
	$Result1 = mysql_query($insertSQL, $altcoins) or die(mysql_error());	
}
else if(!empty($messageText)) //handles user input from facebook messenger chat
{
	$context = "";

	$postval = "{\"input\": {\"text\": \"" . $messageText . "\"}".$context."}";
	
	//sends input to Watson Conversation API

	curl_setopt_array($curl, array(
	  CURLOPT_URL => $url,
	  CURLOPT_RETURNTRANSFER => true,
	  CURLOPT_ENCODING => "",
	  CURLOPT_MAXREDIRS => 10,
	  CURLOPT_TIMEOUT => 30,
	  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
	  CURLOPT_CUSTOMREQUEST => "POST",
	  CURLOPT_POSTFIELDS => $postval,
	  CURLOPT_HTTPHEADER => array(
		"Authorization: Basic <username,password>",
		"Cache-Control: no-cache",
		"Content-type: application/json",
	  ),
	));
	

	$watsonResponse = curl_exec($curl);
	$err = curl_error($curl);

	curl_close($curl);
	
	//handles Watson response

	if ($err) 
	{
		  //echo "cURL Error #:" . $err;
	} 
	else 
	{
		$decodedData = json_decode($watsonResponse);

		$text = $decodedData->output->text[0];

		$context = $decodedData->context;

		$_SESSION['context'] = $context;

		$intent = $decodedData->intents[0]->intent;
		
		//handles facebook user intent result from Watson
		
		if(empty($intent))
		{
			$entities = $decodedData->entities;

			foreach ($entities as $key => $value)
			{
				if($value->entity == "coins")
				{
					$coin = $value->value;
				}
			}

			if(!empty($coin))
			{
				//queries coin info from our database
				
				mysql_select_db($database_altcoins, $altcoins);
				$query_rsSymbolInfo = "SELECT a.*,b.* FROM (SELECT * from coins WHERE Name = '".$coin."' OR  Symbol = '".$coin."') as a INNER JOIN (SELECT * from coinentry) as b ON a.coinid = b.coinid ORDER BY b.entrydate desc LIMIT 1";

				$rsSymbolInfo = mysql_query($query_rsSymbolInfo, $altcoins) or die(mysql_error());
				$row_rsSymbolInfo = mysql_fetch_assoc($rsSymbolInfo);
				$totalRows_rsSymbolInfo = mysql_num_rows($rsSymbolInfo);

				if($totalRows_rsSymbolInfo)
				{
					do {
						
						//displays coin data

						$theSymbol = $row_rsSymbolInfo['Symbol'];

						$symbolInfo = "Name: " . $row_rsSymbolInfo['Name'] . chr(10) . "Symbol: " . $row_rsSymbolInfo['Symbol'] . chr(10) . "Current Price: $" . $row_rsSymbolInfo['Current Price'] . chr(10) . "Change 24hr: " . $row_rsSymbolInfo['Change (24H)'] . "% " . chr(10) . "Change 7 day: " . $row_rsSymbolInfo['Change (7D)'] . "% " . chr(10) . "Buy/Hold/Sell: " . $row_rsSymbolInfo['Buy up Spread'] . chr(10) . "Buy Up To Price: $" . $row_rsSymbolInfo['Buy up to price'] . chr(10);	

					}  while ($row_rsSymbolInfo = mysql_fetch_assoc($rsSymbolInfo));
				}

				if($coin != "BTC" && $coin != "Bitcoin") //no report for Bitcoin
				{
					$answer = ["attachment"=>[
							  "type"=>"template",
							  "payload"=>[
								"template_type"=>"button",
								"text"=>$symbolInfo,
								"buttons"=>[
								  [
									"type"=>"postback",
									"title"=>"View Report",
									"payload"=>$theSymbol . "_report"
								  ]
								]
							  ]
					]];
					
					//populates response to send back to facebook user

					$response = [
						'recipient' => [ 'id' => $senderId ],
						'message' => $answer
					];
				}
				else
				{
					$response = [
						'recipient' => [ 'id' => $senderId ],
						'message' =>  [ 'text' => $symbolInfo]
					];
				}
			}
			else //show sample questions
			{
				$sampleQuestions = "Sorry I didn't understand your question. Please try asking questions like...". chr(10) . chr(10) . "What is the price change for Bitcoin since yesterday?" . chr(10) . "What is your suggested buy price of Litecoin?" . chr(10) . "What are your long-term coins?" . chr(10) . "What are your fast mover coins?" . chr(10) . "Show me your report on SALT" . chr(10) . "How do I join for full access?";
				
				$response = [
					'recipient' => [ 'id' => $senderId ],
					'message' =>  [ 'text' => $sampleQuestions]
				];
			}
		}
		else
		{
			if($intent == "hello")
			{
				//populates clickable buttons with postbacks to display in facebook messenger
				
				 $answer = ["attachment"=>[
					  "type"=>"template",
					  "payload"=>[
						"template_type"=>"button",
						"text"=>$text,
						"buttons"=>[
						  [
							"type"=>"postback",
							"title"=>"Long Term Coins",
							"payload"=>"long"
						  ],
						  [
							"type"=>"postback",
							"title"=>"Fast Mover Coins",
							"payload"=>"fast"
						  ],
					 		[
							"type"=>"postback",
							"title"=>"Watch List Coins",
							"payload"=>"all"
						  ]
						]
					  ]
				]];

				$response = [
					'recipient' => [ 'id' => $senderId ],
					'message' => $answer
				];
			}
			else if($intent == "howto")
			{
				$entities = $decodedData->entities;

				foreach ($entities as $key => $value)
				{
					if($value->entity == "membership")
					{
						$value = $value->value;

						if($value == "become a member")
						{
							$answer = $text;
						}
					}
				}

				$response = [
					'recipient' => [ 'id' => $senderId ],
					'message' =>  [ 'text' => $answer]
				];
			}
			else if($intent == "fast-mover-coins" || $intent == "long-term-coins" || $intent == "all-coins")
			{
				if($intent == "fast-mover-coins")
				{
					if($isSubscriber == true)
					{
						$title = "Fast Movers:";
					}
					else
					{
						$title = "Fast Movers: (2 out of 17). Please JOIN NOW to view full list of our featured coins";
					}

					$term = 0;
				}
				else if($intent == "long-term-coins")
				{
					if($isSubscriber == true)
					{
						$title = "Long Term:";
					}
					else
					{
						$title = "Long Term: (3 out of 19). Please JOIN NOW to view full list of our featured coins";
					}

					$term = 1;
				}
				else if($intent == "all-coins")
				{
					if($isSubscriber == true)
					{
						$title = "Watch List Coins:";
					}
					else
					{
						$title = "Watch List Coins: (5 out of 36). Please JOIN NOW to view full list of our featured coins";
					}

					$term = 2;
				}

				mysql_select_db($database_altcoins, $altcoins);


				if($term == 2)
				{
					$query_rsTermSymbols = "SELECT * FROM coins WHERE 0=0 " . $subscriberStatus . " ORDER BY islongterm desc";
				}
				else
				{
					$query_rsTermSymbols = "SELECT * FROM coins WHERE 0=0 " . $subscriberStatus . " AND islongterm = " . $term;
				}

				$rsTermSymbols = mysql_query($query_rsTermSymbols, $altcoins) or die(mysql_error());
				$row_rsTermSymbols = mysql_fetch_assoc($rsTermSymbols);
				$totalRows_rsTermSymbols = mysql_num_rows($rsTermSymbols);

				if($intent == "all-coins")
				{
					if($totalRows_rsTermSymbols)
					{
						$string = "Watch List Coins: " . chr(10) . chr(10);

						do {

							if($row_rsTermSymbols['islongterm'] == 1)
							{
								$string .=$row_rsTermSymbols['Name'] . " (" . $row_rsTermSymbols['Symbol'] . ") Long" . chr(10);
							}
							else
							{
								$string .=$row_rsTermSymbols['Name'] . " (" . $row_rsTermSymbols['Symbol'] . ") Fast" . chr(10);
							}


						}  while ($row_rsTermSymbols = mysql_fetch_assoc($rsTermSymbols));
					}

					$response = [
						'recipient' => [ 'id' => $senderId ],
						'message' =>  [ 'text' => $string]
					];
				}
				else
				{
					if($isSubscriber == true)
					{
						if($totalRows_rsTermSymbols)
						{
							if($term == 0)
							{
								$string = "Fast Mover Coins: " . chr(10) . chr(10);
							}
							else
							{
								$string = "Long Term Coins: " . chr(10) . chr(10);
							}

							do {

								if($row_rsTermSymbols['islongterm'] == 1)
								{
									$string .=$row_rsTermSymbols['Name'] . " (" . $row_rsTermSymbols['Symbol'] . ") Long" . chr(10);
								}
								else
								{
									$string .=$row_rsTermSymbols['Name'] . " (" . $row_rsTermSymbols['Symbol'] . ") Fast" . chr(10);
								}


							}  while ($row_rsTermSymbols = mysql_fetch_assoc($rsTermSymbols));
						}

						$response = [
							'recipient' => [ 'id' => $senderId ],
							'message' =>  [ 'text' => $string]
						];
					}
					else
					{			
						$termsymbols = array();

						if($totalRows_rsTermSymbols)
						{
							do {

								$termsymbols[] = [

									"type"=>"postback",
									"title"=>$row_rsTermSymbols['Name'] . " (" . $row_rsTermSymbols['Symbol'] . ")",
									"payload"=>$row_rsTermSymbols['Symbol']
								  ];

							}  while ($row_rsTermSymbols = mysql_fetch_assoc($rsTermSymbols));
						}

						$answer = ["attachment"=>[
								  "type"=>"template",
								  "payload"=>[
									"template_type"=>"button",
									"text"=>$title . "." . chr(10). chr(10) . "Select a coin to view price details.",
									"buttons"=>$termsymbols
								  ]
						]];

						$response = [
							'recipient' => [ 'id' => $senderId ],
							'message' => $answer
						];			
					}			
				}
			}
			else if($intent == "rank")
			{
				if($term == 2)
				{
					$query_rsSymbolInfo = "SELECT a.*,b.* FROM (SELECT * from coins WHERE 0=0 " . $subscriberStatus . " ) as a INNER JOIN (SELECT * from coinentry) as b ON a.coinid = b.coinid ORDER BY b.`Change (24H)` desc";
				}
				else
				{
					$query_rsTermSymbols = "SELECT * FROM coins WHERE 0=0 " . $subscriberStatus . " AND islongterm = " . $term;
				}

				$rsTermSymbols = mysql_query($query_rsTermSymbols, $altcoins) or die(mysql_error());
				$row_rsTermSymbols = mysql_fetch_assoc($rsTermSymbols);
				$totalRows_rsTermSymbols = mysql_num_rows($rsTermSymbols);

				if($postback == "all")
				{
					if($totalRows_rsTermSymbols)
					{
						$string = "Watch List Coins: " . chr(10) . chr(10);

						do {

							if($row_rsTermSymbols['islongterm'] == 1)
							{
								$string .=$row_rsTermSymbols['Name'] . " (" . $row_rsTermSymbols['Symbol'] . ") Long" . chr(10);
							}
							else
							{
								$string .=$row_rsTermSymbols['Name'] . " (" . $row_rsTermSymbols['Symbol'] . ") Fast" . chr(10);
							}


						}  while ($row_rsTermSymbols = mysql_fetch_assoc($rsTermSymbols));
					}

					$response = [
						'recipient' => [ 'id' => $senderId ],
						'message' =>  [ 'text' => $string]
					];
				}
			}
			else if($intent == "buy-status-inquiry" || $intent == "buy-price-inquiry" || $intent == "price-change-inquiry" || $intent == "view-report" || $intent == "where-to")
			{
				$entities = $decodedData->entities;

				foreach ($entities as $key => $value)
				{
					if($value->entity == "coins")
					{
						$coin = $value->value;
					}
				}

				if(!empty($coin))
				{			
					mysql_select_db($database_altcoins, $altcoins);

					$query_rsSymbolInfo = "SELECT coinid, ispreview FROM coins WHERE Name = '".$coin."'";

					$rsSymbolInfo = mysql_query($query_rsSymbolInfo, $altcoins) or die(mysql_error());
					$row_rsSymbolInfo = mysql_fetch_assoc($rsSymbolInfo);
					$totalRows_rsSymbolInfo = mysql_num_rows($rsSymbolInfo);

					if($totalRows_rsSymbolInfo)
					{
						if($row_rsSymbolInfo['ispreview'] == 1)
						{
							$coinid = $row_rsSymbolInfo['coinid'];

							$valid = true;
						}
						else if($row_rsSymbolInfo['ispreview'] == 0)
						{
							//not a preview coin. exit and return invalid entry message

							$coinid = $row_rsSymbolInfo['coinid'];

							$valid = false;

							$errorString = $coin . " is not a preview coin. Please JOIN NOW to view " . $coin . " reports and buying info.";
						}
					}
					else
					{
						//not a valid coin. exit and return invalid entry message

						$errorString = "Invalid entry. Please try another coin.";

						$valid = false;
					}

					if($valid == true)
					{
						if($intent == "buy-status-inquiry")
						{
							$query_rsSpreadInfo = "SELECT `Buy up Spread` FROM coinentry WHERE coinid = '".$coinid."' ORDER BY entrydate desc LIMIT 1";

							$rsSpreadInfo = mysql_query($query_rsSpreadInfo, $altcoins) or die(mysql_error());
							$row_rsSpreadInfo = mysql_fetch_assoc($rsSpreadInfo);
							$totalRows_rsSpreadInfo = mysql_num_rows($rsSpreadInfo);

							if($totalRows_rsSpreadInfo)
							{
								do {

									$answer = "The Buy status (BUY, HOLD, SELL) for " . $coin . " is " . $row_rsSpreadInfo['Buy up Spread'];

								}  while ($row_rsSpreadInfo = mysql_fetch_assoc($rsSpreadInfo));
							}
						}
						else if($intent == "buy-price-inquiry")
						{
							$query_rsSpreadInfo = "SELECT `Buy up to price` FROM coinentry WHERE coinid = '".$coinid."'  ORDER BY entrydate desc LIMIT 1";

							$rsSpreadInfo = mysql_query($query_rsSpreadInfo, $altcoins) or die(mysql_error());
							$row_rsSpreadInfo = mysql_fetch_assoc($rsSpreadInfo);
							$totalRows_rsSpreadInfo = mysql_num_rows($rsSpreadInfo);

							if($totalRows_rsSpreadInfo)
							{
								do {

									$answer = "The Buy up to price for " . $coin . " is $" . $row_rsSpreadInfo['Buy up to price'] . 💸;

								}  while ($row_rsSpreadInfo = mysql_fetch_assoc($rsSpreadInfo));
							}
						}
						else if($intent == "price-change-inquiry")
						{
							$query_rsSpreadInfo = "SELECT `Change (24H)` FROM coinentry WHERE coinid = '".$coinid."' ORDER BY entrydate desc LIMIT 1";

							$rsSpreadInfo = mysql_query($query_rsSpreadInfo, $altcoins) or die(mysql_error());
							$row_rsSpreadInfo = mysql_fetch_assoc($rsSpreadInfo);
							$totalRows_rsSpreadInfo = mysql_num_rows($rsSpreadInfo);

							if($totalRows_rsSpreadInfo)
							{
								do {

									$answer = "The price change for " . $coin . " is " . $row_rsSpreadInfo['Change (24H)'] . "%";

								}  while ($row_rsSpreadInfo = mysql_fetch_assoc($rsSpreadInfo));
							}
						}
						else if($intent == "view-report")
						{				
							mysql_select_db($database_altcoins, $altcoins);
							$query_rsSymbolInfo = "SELECT report FROM coins WHERE coinid = '".$coinid."'";

							$rsSymbolInfo = mysql_query($query_rsSymbolInfo, $altcoins) or die(mysql_error());
							$row_rsSymbolInfo = mysql_fetch_assoc($rsSymbolInfo);
							$totalRows_rsSymbolInfo = mysql_num_rows($rsSymbolInfo);

							if($totalRows_rsSymbolInfo)
							{
								do {
									
									if($isSubscriber == false)
									{
										$answer = substr($row_rsSymbolInfo['report'], 0, 500) ."...". chr(10). chr(10) . "Please JOIN NOW to view full report";
									}
									else
									{
										//$answer = $row_rsSymbolInfo['report'];
										$answer = substr($row_rsSymbolInfo['report'], 0, 500) ."...". chr(10). chr(10) . "Please JOIN NOW to view full report";
									}

								}  while ($row_rsSymbolInfo = mysql_fetch_assoc($rsSymbolInfo));
							}
						}
						else if($intent == "where-to")
						{
							$query_rsSpreadInfo = "SELECT markets FROM coins WHERE coinid = '".$coinid."'";

							$rsSpreadInfo = mysql_query($query_rsSpreadInfo, $altcoins) or die(mysql_error());
							$row_rsSpreadInfo = mysql_fetch_assoc($rsSpreadInfo);
							$totalRows_rsSpreadInfo = mysql_num_rows($rsSpreadInfo);

							if($totalRows_rsSpreadInfo)
							{
								do {

									$answer = "You can buy " . $coin . " in these markets: " . chr(10) . chr(10) . $row_rsSpreadInfo['markets'];

								}  while ($row_rsSpreadInfo = mysql_fetch_assoc($rsSpreadInfo));
							}
						}

						 $response = [
							'recipient' => [ 'id' => $senderId ],
							'message' =>  [ 'text' => $answer]
						];
					}
					else
					{
						$response = [
							'recipient' => [ 'id' => $senderId ],
							'message' =>  [ 'text' => $errorString]
						];
					}
				}
				else
				{
					$response = [
						'recipient' => [ 'id' => $senderId ],
						'message' =>  [ 'text' => "Invalid entry. Please try another coin."]
					];
				}
			}
			else
			{
				$response = [
					'recipient' => [ 'id' => $senderId ],
					'message' =>  [ 'text' => $text]
				];
			}
		}		
	}
	
	//sends message to facebook user
	
	$ch = curl_init('https://graph.facebook.com/v2.6/me/messages?access_token='.$accessToken);
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($response));
	curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
	
	if(!empty($input)){
		
		$result2 = curl_exec($ch);
	}
	
	curl_close($ch);
}

?>