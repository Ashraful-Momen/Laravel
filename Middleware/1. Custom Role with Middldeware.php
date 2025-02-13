# Role Management with Middleware : 
------------------------------------
we pass a role with route , then middleware receive those role and check the role is in user like 'customer/admin' . if User valid 
then redirect to customer route file => /route/check_role.php . to the Route::get('/check_role',function(){});

#create middleware  => 
--------------------------
php artisan make:middleware webGuard, 

#app/Http/Middleware/webGuard.php   => 
----------------------------------------

<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class webGuard
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next,$roles)
    {

        if (!Auth::check()) {
            return redirect('/'); // Redirect if the user is not authenticated
        }

        if (Auth::user()->user_type === $roles) {
            
            $request->merge(['user' => Auth::user(), 'role' => $roles]);
            return redirect()->route('check-role'); // Redirect directly to /check-role
            
            //optional part:_________________________________________
             // return redirect()->route('check-role', [
              //  'user' => Auth::user()->id, // Pass user ID as a query parameter
                //'role' => $roles           // Pass role as a query parameter
            //]);
        }

        return redirect('/'); // Redirect if the user does not have the required role


    }
}


#configure the middleware in route middleware , cause we sent the value from route to middleware . 

#app/Http/kernel.php => 
--------------------------
  protected $routeMiddleware = [
       ...... , 
       ......., 
        'role_test' => webGuard::class, //pase it here . 

    ];
    
    
#Now allow the custom route file (which is => /route/check_role.php): app/Providers/RouteServiceProvider.php => 
-----------------------------------------------------------------------------

 public function boot()
    {
        //

        parent::boot();

        // $this->loadRoutesFrom(base_path('routes/check_role.php')); //here we can allow a single route form the custom file . 
        
    }
    

   public function map()
    {
        $this->mapApiRoutes();
        $this->mapWebRoutes();
        $this->mapAdminRoutes();
        $this->mapParentDealerRoutes();
        $this->mapChildDealerRoutes();
        $this->mapCustomerCenterRoutes();
        $this->mapCollectionCenterRoutes();
        
        
        //mapping the custom route file
        $this->mapCustomRoutes(); // this is my custom function for reload the route . 

    }
    
    
      protected function mapCustomRoutes()  //for the web
    {
        Route::middleware('web')
            ->namespace($this->namespace)
            ->group(base_path('routes/check_role.php'));
    }
    
    
    #custom route file > check_role.php => 
    -----------------------------------------
    
<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;

Route::get('/role_test', function () {
    return "Checking Role...";
})->middleware('role_test:customer'); //here , 'role_test is the middleware name' and 'customer' is value pass to the webGuard Middleware .




Route::get('/check-role', function (Request $request) {

    $userId = $request->query('user'); // Get the user ID from the query string
    $role = $request->query('role');   // Get the role from the query string

    $response = json_decode($request, true);

    var_dump("hello customer role users , Your detials => ",$userId,$role, "get request type ", gettype($request->all()));

     // Return a valid JSON response
    return "/n End of the Custom role "; 
})->name('check-role');
