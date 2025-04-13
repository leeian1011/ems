<?php
session_start();
error_reporting(0);
include('includes/dbconnection.php');
if(isset($_POST['login']))
{
    $username=$_POST['username'];
    $password=md5($_POST['password']);
    $sql ="SELECT * FROM admin WHERE UserName=:username and Password=:password";
    $query=$dbh->prepare($sql);
    $query-> bindParam(':username', $username, PDO::PARAM_STR);
    $query-> bindParam(':password', $password, PDO::PARAM_STR);
    $query-> execute();
    $results=$query->fetchAll(PDO::FETCH_OBJ);
    $co = $query->rowCount();
    if($co > 0)
    {
    $_SESSION['odmsaid']=$results[0]->ID;
    $_SESSION['login']=$results[0]->UserName;
    $_SESSION['permission']=$results[0]->AdminName;
    $_SESSION['status']=$results[0]->Status;
    $get=$results[0]->Status;
    if($get=="1")
    { 
      echo "<script type='text/javascript'> document.location ='dashboard_admin.php'; </script>";                  
    } else if($get=="0")
    { 
      echo "<script type='text/javascript'> document.location ='dashboard_event_admin.php'; </script>";                  

    } else
    { 
      echo "<script>
      document.location ='login.php';
      </script>";
    }
    } else{
      echo "<script>
      document.location ='login.php';
    </script>";
    $_SESSION['invalid'] = true;

    }
}
?>
<!DOCTYPE html>
<html lang="en">
<?php @include("includes/head.php");?>
<body>
    <nav class="navbar navbar-expand-lg navbar-light bg-light">
        <a class="navbar-brand" href="/hevent/index.php">HELP Events</a>
        <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav" 
                aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
          <span class="navbar-toggler-icon"></span>
        </button>
    </nav>
    <div class="container-scroller">
        <div class="container-fluid page-body-wrapper full-page-wrapper">
            <div class="content-wrapper d-flex align-items-center auth p-0">
                <div class="row flex-grow">
                    <div class="col-md-4 p-0">
                        <div class="auth-form-light text-left p-5">
                            <div class="brand-logo" align="center">
                                <img class="img-avatar mb-3" src="https://png.pngtree.com/png-vector/20230525/ourmid/pngtree-help-red-letters-3d-illustration-vector-png-image_7108569.png" alt=""><br>
                                <h4 class="text-muted mt-4">
                                    Welcome!
                                </h4>
                            </div>
                <form role="form" id=""  method="post" enctype="multipart/form-data" class="">  
                  <?php if (isset($_SESSION['invalid'])) {
                  echo "<p> Invalid details! </p>";
                  }?>
                                <div class="form-group first">
                                    <input type="text" class="form-control form-control-lg" name="username" id="exampleInputEmail1" placeholder="Username" required>
                                </div>
                                <div class="form-group last">
                                    <input type="password" name="password" class="form-control form-control-lg" id="exampleInputPassword1" placeholder="Password" required>
                                </div>
                                <div class="mt-3">
                                    <button name="login" class="btn btn-block btn-info btn-lg font-weight-medium auth-form-btn">LOGIN</button>
                                </div>
                            </form>
                        </div>
                    </div>
                    <div class="col-md-8 p-0">
              <div id="carouselExampleControls" class="carousel slide" data-ride="carousel">
   <div class="carousel">
        <div class="carousel-item active carousel-slide">
            <img src="https://university.help.edu.my/wp-content/uploads/2023/12/auditorium-copy.jpg" alt="Image 1">
        </div>
                </div>    
                    </div>
                </div>
            </div>
            
        </div>
        
    </div>
</body>
</html>
