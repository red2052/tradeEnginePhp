<?php

class OrderRegister_model extends CI_Model {

    public $redis;

	public function __construct()
	{
        parent::__construct();
        $this->redis = new Redis(); 
        if($this->redis->isConnected() === false)
        {
            $this->redis->connect('127.0.0.1', 6379, 3.5);
        }
	}	

	public function process()
	{
		print("model process\n");
		        
        if($this->redis->isConnected() === true)
        {        
            while(true){
                //redis 큐로부터 거래요청데이터 가져오기
                $arr = $this->redis->brPop('traderequest', 0);
                
                if($arr[0] == "traderequest") {
                    $request = json_decode($arr[1]);
                    //연결유지또는 재연결
                    $this->db->reconnect();
                    //매도/매수구분
                    if($request->type == "buy"){
                        $this->requestBuyProcess($request);
                    } else if($request->type == "sell"){
                        $this->requestSellProcess($request);
                    }
                } 
                
            }		
        } else {
            echo "redis is not connected...\n";
        }
    }

    public function requestBuyProcess($request){
        echo "buy..\n";
        $buyer_amount = $request->amount;
        $sql = " SELECT * "
              ." FROM sell_order_request "
              ." WHERE "
              ."    COIN = ? "
              ."    AND MARKET = ? "
              ."    AND UNIT_PRICE <= ? "
              ." ORDER BY UNIT_PRICE ASC, CREATIONDATE ASC ";
        $query = $this->db->query($sql, array($request->coin , $request->market ,$request->unit_price)); 
        
        $completeArr = array();        

        $this->db->trans_start();

        echo "매도가능 대기열 건수: " . $query->num_rows() ."\n";

        if ($query->num_rows() > 0)
        {
            foreach ($query->result_array() as $row)
            {
                if($buyer_amount >= $row['REMAINING_AMOUNT'])
                {
                    $sql =   " INSERT INTO  order_complete "
                            ." SET MB_IDX = ?, "
                            ." TYPE = ?, "
                            ." COIN = ? ,"
                            ." MARKET = ? ,"
                            ." PRICE = ? ,"
                            ." AMOUNT = ? ,"
                            ." UNIT_PRICE = ? ,"
                            ." FEE = ? ";
                    $sellArr = array(   $row['MB_IDX'], '0' , $row['COIN'] , $row['MARKET'],
                                        $row['REMAINING_AMOUNT'] * $row['UNIT_PRICE'] , $row['REMAINING_AMOUNT'] , $row['UNIT_PRICE'] , 
                                        $row['REMAINING_AMOUNT'] * $row['UNIT_PRICE'] * $row['FEERATE'] );    
                    $completeArr[] = $sellArr;

                    $query = $this->db->query($sql, $sellArr);   
                    $sql = "   INSERT INTO  order_complete "
                            ." SET MB_IDX = ?, "
                            ."  TYPE = ?, "
                            ."  COIN = ? ,"
                            ."  MARKET = ? ,"
                            ."  PRICE = ? ,"
                            ."  AMOUNT = ? ,"
                            ."  UNIT_PRICE = ? ,"
                            ."  FEE = ? ";
                    $buyArr = array(   $request->mb_idx, '1' , $row['COIN'] , $row['MARKET'],
                                        $row['REMAINING_AMOUNT'] * $row['UNIT_PRICE'] , $row['REMAINING_AMOUNT'] , $row['UNIT_PRICE'] , 
                                        $row['REMAINING_AMOUNT'] * $row['UNIT_PRICE'] * $request->feerate );    
                    $completeArr[] = $buyArr;                            
                    $query = $this->db->query($sql, $buyArr );      
                                                            
                    $sql =   " DELETE FROM sell_order_request "
                            ." WHERE "
                            ."  IDX = ? ";
                    $query = $this->db->query($sql, array($row['IDX']));   
                    
                    //$this->redis->publish('ordercpl', 'order'); // send message.
                    
                } else {

                    $sql =   " INSERT INTO  order_complete "
                            ." SET MB_IDX = ?, "
                            ."  TYPE = ?, "
                            ."  COIN = ? ,"
                            ."  MARKET = ? ,"
                            ."  PRICE = ? ,"
                            ."  AMOUNT = ? ,"
                            ."  UNIT_PRICE = ? ,"
                            ."  FEE = ? ";
                    $sellArr = array(   $row['MB_IDX'], '0' , $row['COIN'] , $row['MARKET'],
                                        $buyer_amount * $row['UNIT_PRICE'] , $buyer_amount  , $row['UNIT_PRICE'] , 
                                        $buyer_amount * $row['UNIT_PRICE'] * $row['FEERATE'] );    
                    $completeArr[] = $sellArr;                            
                    $query = $this->db->query($sql, $sellArr );   
                    $sql =   " INSERT INTO  order_complete "
                            ." SET MB_IDX = ?, "
                            ." TYPE = ?, "
                            ." COIN = ? ,"
                            ." MARKET = ? ,"
                            ." PRICE = ? ,"
                            ." AMOUNT = ? ,"
                            ." UNIT_PRICE = ? ,"
                            ." FEE = ? ";
                    $buyArr = array( $request->mb_idx, '1' , $row['COIN'] , $row['MARKET'],
                                     $buyer_amount * $row['UNIT_PRICE'] , $buyer_amount , $row['UNIT_PRICE'] , 
                                     $buyer_amount * $row['UNIT_PRICE'] * $request->feerate );    
                    $completeArr[] = $buyArr;                            
                    $query = $this->db->query($sql, $buyArr );       
                                                            
                    $sql =   " UPDATE  sell_order_request "
                            ." SET "
                            ."  REMAINING_AMOUNT = REMAINING_AMOUNT - ? "
                            ." WHERE "
                            ."  IDX = ? ";
                    $query = $this->db->query($sql, array( $buyer_amount, $row['IDX']));    
                    //$this->redis->publish('ordercpl', 'order2'); // send message.                                                        
                }     
                
                $buyer_amount -= $row['REMAINING_AMOUNT'];

                if($buyer_amount <= 0) {
                    break;
                }

            }

            if($buyer_amount > 0) {
                $sql =   " INSERT INTO  buy_order_request "
                        ." SET MB_IDX = ?, "
                        ." COIN = ? ,"
                        ." MARKET = ? ,"
                        ." PRICE = ? ,"
                        ." AMOUNT = ? ,"
                        ." UNIT_PRICE = ? ,"
                        ." FEERATE = ? ,"
                        ." REMAINING_AMOUNT = ? ";
                $query = $this->db->query($sql, array($request->mb_idx, $request->coin , $request->market , 
                                                    $request->price , $request->amount , $request->unit_price , 
                                                    $request->feerate , $buyer_amount));    
            }

        } else {
            $sql = " INSERT INTO  buy_order_request "
                ." SET MB_IDX = ?, "
                ." COIN = ? ,"
                ." MARKET = ? ,"
                ." PRICE = ? ,"
                ." AMOUNT = ? ,"
                ." UNIT_PRICE = ? ,"
                ." FEERATE = ? ,"
                ." REMAINING_AMOUNT = ? ";
            $query = $this->db->query($sql, array($request->mb_idx, $request->coin , $request->market , 
                                                $request->price , $request->amount , $request->unit_price , 
                                                $request->feerate , $request->amount));            
        } 
        
        $this->db->trans_complete();  
        $resultArr = array (
                        "coin"=>$request->coin,
                        "market"=>$request->market,
                        "ymd"=>gmdate("Ymd"),
                        "completeArr"=>$completeArr
                    );     
        $this->redis->publish('ordercpl', json_encode($resultArr)); // send message.
    }

    public function requestSellProcess($request){
        echo "sell..\n";
        $seller_amount = $request->amount;

        $sql = " SELECT * "
              ." FROM buy_order_request "
              ." WHERE "
              ."    COIN = ? "
              ."    AND MARKET = ? "
              ."    AND UNIT_PRICE >= ? "
              ." ORDER BY UNIT_PRICE DESC, CREATIONDATE ASC ";
        $query = $this->db->query($sql, array($request->coin , $request->market ,$request->unit_price));   

        $completeArr = array();

        $this->db->trans_start();

        echo "매수가능 대기열 건수: " . $query->num_rows() ."\n";
        if ($query->num_rows() > 0)
        {
            foreach ($query->result_array() as $row)
            {
                if($seller_amount >= $row['REMAINING_AMOUNT'])
                {
                    $sql = " DELETE FROM buy_order_request "
                    ." WHERE "
                    ."  IDX = ? ";
                    $query = $this->db->query($sql, array($row['IDX']));

                    $sql =   " INSERT INTO  order_complete "
                            ." SET MB_IDX = ?, "
                            ." TYPE = ?, "
                            ." COIN = ? ,"
                            ." MARKET = ? ,"
                            ." PRICE = ? ,"
                            ." AMOUNT = ? ,"
                            ." UNIT_PRICE = ? ,"
                            ." FEE = ? ";
                    $buyArr = array( $row['MB_IDX'], '1' , $row['COIN'] , $row['MARKET'],
                                     $row['REMAINING_AMOUNT'] * $row['UNIT_PRICE'] , $row['REMAINING_AMOUNT'] , $row['UNIT_PRICE'] , 
                                     $row['REMAINING_AMOUNT'] * $row['UNIT_PRICE'] * $row['FEERATE'] );    
                    $completeArr[] = $buyArr;                            
                    $query = $this->db->query($sql, $buyArr);   
                    $sql = "   INSERT INTO  order_complete "
                            ." SET MB_IDX = ?, "
                            ."  TYPE = ?, "
                            ."  COIN = ? ,"
                            ."  MARKET = ? ,"
                            ."  PRICE = ? ,"
                            ."  AMOUNT = ? ,"
                            ."  UNIT_PRICE = ? ,"
                            ."  FEE = ? ";
                    $sellArr = array(   $request->mb_idx, '0' , $row['COIN'] , $row['MARKET'],
                                        $row['REMAINING_AMOUNT'] * $row['UNIT_PRICE'] , $row['REMAINING_AMOUNT'] , $row['UNIT_PRICE'] , 
                                        $row['REMAINING_AMOUNT'] * $row['UNIT_PRICE'] * $request->feerate );    
                    $completeArr[] = $sellArr;                            
                    $query = $this->db->query($sql, $sellArr );         
                                                            
                    //$this->redis->publish('ordercpl', 'requestSellProcess'); // send message.
                } else {
                    $sql =   " UPDATE  buy_order_request "
                            ." SET "
                            ."  REMAINING_AMOUNT = REMAINING_AMOUNT - ? "
                            ." WHERE "
                            ."  IDX = ? ";
                    $query = $this->db->query($sql, array( $seller_amount, $row['IDX']));

                    $sql =   " INSERT INTO  order_complete "
                            ." SET MB_IDX = ?, "
                            ."  TYPE = ?, "
                            ."  COIN = ? ,"
                            ."  MARKET = ? ,"
                            ."  PRICE = ? ,"
                            ."  AMOUNT = ? ,"
                            ."  UNIT_PRICE = ? ,"
                            ."  FEE = ? ";

                    $buyArr = array( $row['MB_IDX'], '1' , $row['COIN'] , $row['MARKET'],
                                     $seller_amount * $row['UNIT_PRICE'] , $seller_amount  , $row['UNIT_PRICE'] , 
                                     $seller_amount * $row['UNIT_PRICE'] * $row['FEERATE'] );    
                    $completeArr[] = $buyArr;                                
                    $query = $this->db->query($sql, $buyArr);   
                    $sql =   " INSERT INTO  order_complete "
                            ." SET MB_IDX = ?, "
                            ." TYPE = ?, "
                            ." COIN = ? ,"
                            ." MARKET = ? ,"
                            ." PRICE = ? ,"
                            ." AMOUNT = ? ,"
                            ." UNIT_PRICE = ? ,"
                            ." FEE = ? ";
                    $sellArr = array( $request->mb_idx, '0' , $row['COIN'] , $row['MARKET'],
                                     $seller_amount * $row['UNIT_PRICE'] , $seller_amount , $row['UNIT_PRICE'] , 
                                     $seller_amount * $row['UNIT_PRICE'] * $request->feerate );    
                    $completeArr[] = $sellArr;                               
                    $query = $this->db->query($sql, $sellArr);                           
                    //$this->redis->publish('ordercpl', 'requestSellProcess'); // send message.
                }     
                
                $seller_amount -= $row['REMAINING_AMOUNT'];

                if($seller_amount <= 0) {
                    break;
                }

            }

            if($seller_amount > 0) {
                $sql =   " INSERT INTO  sell_order_request "
                        ." SET MB_IDX = ?, "
                        ." COIN = ? ,"
                        ." MARKET = ? ,"
                        ." PRICE = ? ,"
                        ." AMOUNT = ? ,"
                        ." UNIT_PRICE = ? ,"
                        ." FEERATE = ? ,"
                        ." REMAINING_AMOUNT = ? ";
                $query = $this->db->query($sql, array($request->mb_idx, $request->coin , $request->market , 
                                                    $request->price , $request->amount , $request->unit_price , 
                                                    $request->feerate , $seller_amount));    
            }

        } else {
            $sql = " INSERT INTO  sell_order_request "
                ." SET MB_IDX = ?, "
                ." COIN = ? ,"
                ." MARKET = ? ,"
                ." PRICE = ? ,"
                ." AMOUNT = ? ,"
                ." UNIT_PRICE = ? ,"
                ." FEERATE = ? ,"
                ." REMAINING_AMOUNT = ? ";
            $query = $this->db->query($sql, array($request->mb_idx, $request->coin , $request->market , 
                                                $request->price , $request->amount , $request->unit_price , 
                                                $request->feerate , $request->amount));            
        } 
        
        $this->db->trans_complete();
        $resultArr = array (
            "coin"=>$request->coin,
            "market"=>$request->market,
            "ymd"=>gmdate("Ymd"),
            "completeArr"=>$completeArr
        );           
        $this->redis->publish('ordercpl', json_encode($resultArr)); // send message.
    }    
    
	public function register($paramMap)
	{
        $returnVal = 0;
        if($this->redis->isConnected() === true)
        { 
            $returnVal = $this->redis->lPush('traderequest', json_encode($paramMap));
            //log_message('debug', "returnVal: ".$returnVal);
        }
        return $returnVal;        
	}    
}