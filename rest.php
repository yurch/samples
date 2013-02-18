<?php
/*
 * in real life code like this will never met in one file but for an example...
 * own ToDo list with Api and Json
 * to proper work need webserver configuration like in .htaccess
 * 
 */
 
//in order to use session as storage
 session_start();
 
/*
 *
 * Part I. The Rest Web Service
 * 
 * "methods" request methods supported by server
 * "call" api method 
 *
 */


class RestWS {
	//supported methods
	const GET = "GET";
	const POST = "POST";
	const PUT = "PUT";
	const DELETE = "DELETE";

	//url params
	private $url_params = array();
	
	//request params
	private $req_params = array();
	
	//api calls params
	private $registred_calls = array();
	
	//api name
	private $api_name = null;
	
	//api version
	private $api_version = null;
    
    //call params
    protected $params = array();

	//api ws requires name and version
	function __construct($ws_name, $version) {
		if(!is_string($ws_name) || $ws_name == "" ){
			throw new Exception("api name is required and must be string");
		}
		
		if(!is_string($version) || $version == "" ){
			throw new Exception("version is required and must be string");
		}

		$this->api_name = $ws_name;
		$this->api_version = $version;
	}
	
	//process uri
	protected function process($_SERVER){

		//if there is any api call for the method
		if(isset($this->registred_calls[$_SERVER['REQUEST_METHOD']])){

			//teke them
			$calls = $this->registred_calls[$_SERVER['REQUEST_METHOD']];
			
			//split url to components
			$this->url_params = preg_split("/\//", $_SERVER["REQUEST_URI"]);
			
			//first url param must be this service(api) name
			if($this->url_params[1] !== $this->api_name){
				return null;
			}
			
			//second - api version
			if($this->url_params[2] !== $this->api_version){
				return null;
			}
			
			//now, compare url with registred url of calls
			//and gather parameters  method
			
			//it will be needed to get parameters from url
			$pattern = "/(?<={)\w+(?=})/";
			
			//go through other url components
			
			//init white list with all calls 
			$white_list = $calls;
			//the call url component increment
			$j = 1;

			//loop increment start from 3 as other first is empty and other two are checked
			for ($i = 3; $i < count($this->url_params); $i++){

				//list of matched calls
				$filtered_list = array();
				
				//filter calls
				//loop through call
				foreach ($white_list as $call){
					//is it url part or param
					//match component
					preg_match($pattern, $call['URL'][$j], $matches);

					//if it is param save it
					if (count($matches) > 0){
						$call["PARAMETERS"][$matches[0]] = $this->url_params[$i];
						
						//pass filtration
						$filtered_list[] = $call;
						
					} else {
						//if not compare to url component
						if($call['URL'][$j] === $this->url_params[$i]){
							
							//pass filtration
							$filtered_list[] = $call;
						
						}
						//next call url
					}
				}
				
				//update white list for the next loop
				$white_list = $filtered_list;
				
				//next call url component
				$j++;
				
				//next url component of request 
			}
			
			//found it 
			if(count($white_list) === 1){
			    
                $call = $white_list[0];
                
                //add url params to be visible for child api
                $this->params = $call["PARAMETERS"];
                
                //at this point get request params
                parse_str(file_get_contents('php://input'), $this->req_params);
                
                //add them to call params, rename if needed
                foreach ($this->req_params as $key => $value) {
                    if(isset($this->params[$key])){
                        $key = "REQ_".$key;
                    }
                    
                    $this->params[$key] = $value;
                }
                
				return $white_list[0]["NAME"];

			//can't be!
			} else if(count($white_list) > 0){
				throw new Exception("more than one call match");

			//nope, no call	
			} else {
				return null;
			}
			
		} else {
			//no call for the method
			return null;
		}
	}
	

	// request method, url and call name are required
	// url should be unique
	protected function registerApiCall($method, $url, $name){
		
		//check required parameters
		if(!is_string($method) || $method == "" ){
			throw new Exception("method is required and must be string");
		}
		
		if(!is_string($url) || $url == "" ){
			throw new Exception("url is required and must be string");
		}
		
		if(!is_string($name) || $name == "" ){
			throw new Exception("name is required and must be string");
		}
		
		//if there was not register any api call for the method
		//create method array
		if(!isset($this->registred_calls[$method])){
			$this->registred_calls[$method] = array();
		}
		
		$urlArr = preg_split("/\//", $url);
		//url should be unique
		foreach ($this->registred_calls[$method] as $call){
			if($call["URL"] == $urlArr) {
				throw new Exception("url should be unique");
			}
		}
		
		//put method in to list
		$this->registred_calls[$method][] = array("URL" => $urlArr, "NAME" => $name, "PARAMETERS" => array());
	}
}
?>

<?php
/*
 *
 * Part II. The API
 *
 * Let's make some ToDo list
 *
 */

//Task reprezentation.  
class Task {
	
	private $data = array("id" => null, "title" => "", "description" => "", "completed" => false, "deleted" => false);

	public function __construct($title, $description, $completed, $deleted){
		$this->data["title"] = $title;
		$this->data["description"] = $description;
		$this->data["completed"] = $completed;
		$this->data["deleted"] = $deleted;
	}
    
    public function setId($id){
        $this->data["id"] = $id;
    }
    
    public function getId(){
        return $this->data["id"];
    }
	
	public function toJSON(){
		return json_encode($this->data);
	}

	public function toArray(){
		return $this->data;
	}
}

class TaskError {
	
	private $data = array("error" => null, "code" => "", "description" => "");

	public function __construct($error, $title, $description){
		$this->data["error"] = $error;
		$this->data["code"] = $code;
		$this->data["description"] = $description;
	}
	
	public function toJSON(){
		return json_encode($this->data);
	}

	public function getArray(){
		return $this->data;
	}
}

class SomeStorage {
    
    private $incr = 0;
    
    function __construct() {
        if(!isset($_SESSION["LIST"])){
            $_SESSION["LIST"] = array();
            $_SESSION["task_incr"] = $this->incr;
        } else {
            $this->incr = $_SESSION["task_incr"];
        }
    }
	
    public function put($task){
        $id = "task_".$this->incr;
        
        $task->setId($id);
        
        $_SESSION["LIST"][$id] = $task;
        
        $_SESSION["task_incr"] = ++$this->incr;
    }
    
    public function get(){
        $result = array();
        
        foreach ($_SESSION["LIST"] as $key => $value) {
            $result[] = $value;
        }
        
        return $result;
    }
    
    public function del($id){
        unset($_SESSION["LIST"][$id]);
    }
}

class TaskApi extends RestWS{

	const version = "v1";

	const name = "tasks";
	
	private $storage = null;
    
	function __construct() {
	    //init ws
		parent::__construct(self::name, self::version);
        
        //register calls 
        $this->registerApiCall(RestWS::GET, "/list", "getTasks");
        $this->registerApiCall(RestWS::POST, "/list/task", "addTasks");
        $this->registerApiCall(RestWS::DELETE, "/list/task/{id}", "delTask");
        
        //connect storage
        $this->storage = new SomeStorage();
	}
    
    
    public function apiCall($_SERVER){
        $callName = $this->process($_SERVER);
        
        if($callName != null){
                
            return $this->$callName();
        } else {
            //in this example it mean show the page
            //in other cases better return error json
            return false;
        }
    }
    
    /*
     *
     * Api implementation
     *  
     */
    
    private function getTasks (){
        
        $tasks = $this->storage->get();
        
        $arr = array();
        
        foreach ($tasks as $key => $task) {
            $arr[] = $task->toArray();
        }
        
        return array("list" => $arr);
    }
    
    private function addTasks (){
        
        $title = $this->params["KEY"];
        $descr = $this->params["VALUE"];
        
        $task = new Task($title, $descr, false, false);
        
        $this->storage->put($task);
        
        return $task->toArray();
    }
    
    private function delTask (){
        $id = $this->params["id"];
        
        $tasks = $this->storage->del($id);
        
        return array("deleted"=>true);
    }

}
?>

<?php
/*
 *
 * Part III. Use it
 *
 */

	 
	 $api = new TaskApi();
     
     $result = $api->apiCall($_SERVER);
     
     if($result !== false) {
         //api method was used
         //return json
         echo json_encode($result);
     } else {
        //or show page
?>
<html>
    <head>
        <!--TODO write something -->
    </head>
    <body>
        <form id="addNew">
        	<input type="text" id="KEY" name="KEY"/>
        	<input type="text"  id="VALUE" name="VALUE"/>
        	<input type="button" id="doPOST" value="POST task"/>
        </form>
        
        <div class="taskList">
            <div>GET Tasks</div>
            <ul id="list">
                
            </ul>
        </div>
        
        <script src="//ajax.googleapis.com/ajax/libs/jquery/1.9.1/jquery.min.js"></script>
        <script type="text/javascript">
        $(function(){
        
        	$('#doPOST').click(function(){
        		add();
        	});
        	
        	$('body').delegate('.delete', 'click', function(){
        	    var id = $(this).parent().attr('id');
        		del(id);
        		return false;
        	});
        	
        	get();
        	console.log("asd");
        	
        	function add(){
        		var params = {}
        		$('#addNew input[type="text"]').each(function(){
        			params[$(this).attr('id')] = $(this).val();
        			});
        		
        		$.ajax({
        		    url: '/tasks/v1/list/task',
        		    dataType: 'json',
        			type: 'POST',
        			data: params,
        			success: function (){
        			    get();
        			}
        		});
        	}
        	
        	function get(){
                 $.ajax({
                    url: '/tasks/v1/list',
                    dataType: 'json',
                    success: function (json){
                        update(json);
                    }
                });
            }
        	
        	function del(id){
                 $.ajax({
                     url: '/tasks/v1/list/task/'+id,
                     dataType: 'json',
                     type: 'DELETE',
                     success: function (json){
                        get();
                     }
                });
            }
            
            function update(json){
                if(json){
                    var liArr = [];
                    
                    for(var l = json.list.length, i=0; l--; i++){
                        liArr.push(
                            $('<li/>', {id: json.list[i].id}).text(json.list[i].title + ' - ' + json.list[i].description).append(
                                $('<a/>',{"class": "delete", href: "#"}).text('DELETE task')
                                )[0]
                            );
                    }
                    
                    $('#list').empty().append(liArr);
                }
            }
        });
        </script>
        <style>
            a {
                margin: 1.5em;
            }
            /*TODO improve styles*/
        </style>
    </body>
</html>
<?php
     }
?>
