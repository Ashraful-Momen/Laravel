#Service Container : (In class like setter / getter concept. )
------------------------
array[$kay] = $value ; // we add element in array . 


in Laravel app() . contain total service . Service add as like array element add , array[$key] = $value .

example : 
-----------
class ServiceContainer{
	
	private $service = [];

	function set($key, $value){
		$this->service[$key] = $value
	}
	
	function get($key){
		
		//handle error , if array_key exist in $service then show the service either through the execptions. 
		return $this->service[$key];
	}

}

#how can we bind a service in Laravel  : 
-----------------------------------------
app()->bind('getName',Person::class);



#how can we bind / set / add a service [as like array set value / get value ]

#show the total service : 
-------------------------

dd(app()); // in bind(30) show the all services. total 30 services . 
