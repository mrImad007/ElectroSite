<?php

class User extends Controller{

    protected $pdo;
    protected $users;

    public function __construct(){
        $this->pdo = new Database;
        $this->users = $this->model('Users');
    }
    //--------------------------------------------
    public function register(){
        if(isset($_POST['name']) && isset($_POST['image']) && isset($_POST['email']) && isset($_POST['pwd']) && isset($_POST['tel']) && isset($_POST['adress'])){
            $name = $_POST['name'];
            $image = $_POST['image'];
            $email = $_POST['email'];
            $pwd = $_POST['pwd'];
            $tel = $_POST['tel'];
            $adress = $_POST['adress'];

            $this->users->register($name,$image,$email,$pwd,$tel,$adress);
            
            $this->view('Templates/UserSign');
        };
    }

    //--------------------------------------------
    public function logIn(){
        if(isset($_POST['email']) && isset($_POST['pwd'])){
            $email = $_POST['email'];
            $pwd = $_POST['pwd'];

            $return = $this->users->log($email,$pwd);

            if($return){
                $_SESSION['user'] = $email;
                header('Location:'.URLROOT.'ElectroSite/public/Pages/cart');
            }else{
                $this->view('Templates/UserSign');
            }
        };
    }

    //--------------------------------------------
    public function logout(){
        if(isset($_SESSION['user'])){
            session_start();
            session_unset();
            session_destroy();
        }
        header('Location:'.URLROOT.'ElectroSite/public/Pages/index');
    }
    //--------------------------------------------
    public function addCart(){
        if(isset($_POST['label']) && isset($_POST['qtt'])){
            
            $name = $_POST['label'];
            $qtt = $_POST['qtt'];
            $response = $this->users->getProduct($name);

            if(!$response){
            $data = [
                'user' => 'imad',
                'label' => $_POST['label'],
                'qtt' => $_POST['qtt'],
                'total' => 666
            ];

            $this->users->addtocart($data);

            }else{
                $this->users->updateQtt($qtt,$name);
            }

            header('Location:'.URLROOT.'ElectroSite/public/Pages/cart');
        }
    }

    //--------------------------------------------
    public function showOne(){
        if(isset($_POST['id'])){
            $id = $_POST['id'];

            $product = $this->users->lonely($id);

            $data= [
                'id' => $product->id,
                'image' => $product->image,
                'label' => $product->label,
                'codeBarre' => $product->codeBarre,
                'buyP' => $product->buyP,
                'sellP' => $product->sellP,
                'finalP' => $product->finalP,
                'category_name' => $product->name,
                'description' => $product->description
            ];

            $this->view('Templates/Product-detail',$data);
        }
    }

    //--------------------------------------------
    public function deleteCart(){
        
        if(isset($_POST['This_id'])){
            $id = $_POST['This_id'];
            
            $this->users->deleteFromCart($id);

            header('Location:'.URLROOT.'ElectroSite/public/Pages/cart');

        }
    }
    //--------------------------------------------
    public function updateCart(){
        if(isset($_POST['productId']) && isset($_POST['qtt'])){
            $data = [
                'id' => $_POST['productId'],
                'qtt' => $_POST['qtt']
            ];

            $this->users->updateCart($data);

            die('done');
            header('Location:'.URLROOT.'ElectroSite/public/Pages/cart');

        }
    }

    public function updateProductCart() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            for ($i = 0; $i < count($_POST['productId']); $i++) {
                $data = [
                    'id' => $_POST['productId'][$i],
                    'qtt' => $_POST['qtt'][$i],
                ];
                
                $this->users->updateCart($data);
            }

            
                header('Location:'.URLROOT.'ElectroSite/public/Pages/cart');
            
        }
    }
    //--------------------------------------------
    public function sendCommande() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if(isset($_POST['products']) && isset($_POST['quantity'])){

            $products = $_POST['products'];
            $quantity = $_POST['quantity'];
            $email = $_SESSION['user'];

            $id = $this->users->getUser($email);

            $data = [
                'id_client' => $id,
                'creation_date' => date('d-m-y'),
            ];

            $idCommande = $this->users->createCommande($data);
            
   
            if ($idCommande) {

                for ($i = 0; $i < count($products); $i++){
                    $data = [
                        'id_product' => $products[$i],
                        'id_commande' => $idCommande,
                        'quantite' => $quantity[$i],
                    ];
                    $this->users->addProductCommande($data);
                }
                
                
                if ($this->users->finishCommande()) {
                    $total = $this->users->totalPrice($idCommande);
                    $this->users->updatePrice($idCommande,$total);
                    $this->users->clearPanier();
                    $command = $this->users->facture($idCommande);
                    $data = [
                        'command' => $command
                    ];
                    
                    $this->view('Templates/Facture',$data);
                } else {
                    die('SOMETHING WRONG ???');
                }
            }
        }else{
            $this->view('Templates/error');
        }
    }
}
    //--------------------------------------------
    public function userCommands(){
        if(isset($_POST['user_id'])){
            $user_id = $_POST['user_id'];
            $commands = $this->users->getCommands($user_id);

        }
    }

    //--------------------------------------------
    public function acceptCommands(){
        if(isset($_POST['command_id'])){
            $data = [
                'shipping_date' => date('d-m-y'),
                'id' => $_POST['command_id']
            ];

            $this->users->accept($data);
            header('Location:'.URLROOT.'ElectroSite/public/Admin/show');
        }
    }

    //--------------------------------------------
    public function rejectCommands(){
        if(isset($_POST['command_id'])){
            $id = $_POST['command_id'];

            $this->users->reject($id);
            header('Location:'.URLROOT.'ElectroSite/public/Admin/show');
        }
    }
    //--------------------------------------------
    public function checkLogin(){
        
        if(isset($_SESSION['user'])){
            $this->sendCommande();
        }else{
            $this->view('Templates/UserSign');
        }
    }
}




?>