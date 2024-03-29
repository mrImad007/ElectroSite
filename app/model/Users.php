<?php
class Users{

    protected $pdo;
    
    //--------------------------------------------
    public function __construct(){
        $this->pdo = new Database;
    }

    //--------------------------------------------LogIn & Register
    public function register($name,$image,$email,$pwd,$tel,$adrs){

        $query = "INSERT INTO `users` (`name`, `image`, `email`, `password`, `phone`, `adress`) VALUES ('$name', '$image', '$email', '$pwd', '$tel', '$adrs')";
        $this->pdo->query($query);
    }

    //--------------------------------------------
    public function log($email,$pwd){
        
        $query = "SELECT * FROM `users` WHERE `email`= '$email' AND `password` = '$pwd' ";
        $this->pdo->prepare($query);
        $user = $this->pdo->resultSet();

        return $user;
    }

    //--------------------------------------------Cart management
    public function seeCart(){
        $query = "SELECT `cart`.* , `products`.`label`,`products`.`image`,`products`.`sellP`, `users`.`name` FROM `cart` INNER JOIN `products` ON `cart`.`id_product` = `products`.`id` INNER JOIN `users` ON  `cart`.`id_user` = `users`.`id`";
        $this->pdo->prepare($query);
        $myprods = $this->pdo->resultSet();

        return $myprods;

    }

    //--------------------------------------------
    public function addtocart($data){

        $query = "INSERT INTO `cart` (`id_user`, `id_product`, `quantity`, `total_price`) VALUES ((SELECT id FROM users WHERE `name` = :user), (SELECT id FROM products WHERE label = :label), :qtt, :ttl)";
        $this->pdo->prepare($query);

        $this->pdo->bind(':user', $data['user']);
        $this->pdo->bind(':ttl',$data['total']);
        $this->pdo->bind(':label',$data['label']);
        $this->pdo->bind(':qtt', $data['qtt']);

        $this->pdo->execute();

    }

    //--------------------------------------------
    public function lonely($id){

        $query = "SELECT products.*, category.name FROM `products` INNER JOIN category ON products.category_id = category.id WHERE products.id = '$id'";
        $this->pdo->prepare($query);
        $product = $this->pdo->single();
        return $product;
    }

    //--------------------------------------------
    public function deleteFromCart($id){
        $query = "DELETE FROM `cart` WHERE  `id_product`= :id";
        $this->pdo->prepare($query);
        $this->pdo->bind(':id',$id);
        $this->pdo->execute();

    }

    //--------------------------------------------
    public function updateCart($data){
        $query = "  UPDATE `cart`
                    SET   `quantity` = :qtt
                    WHERE `id_product` = :id";
                
        $this->pdo->prepare($query);
        $this->pdo->bind(':id', $data['id']);
        $this->pdo->bind(':qtt', $data['qtt']);
        $this->pdo->execute();
    }

    //-------------------------------------------
    public function createCommande($data) {
        $ttl = 0;
        $this->pdo->beginTransaction();
        $this->pdo->prepare("INSERT INTO `commandes`(`creation_date`, `shipping_date`, `user_id`, `total_price`) VALUES (:crDate, NULL, :user, :ttl)");
        
        $this->pdo->bind(':user', $data['id_client']->id);
        $this->pdo->bind(':crDate', $data['creation_date']);
        $this->pdo->bind(':ttl', $ttl);

        $this->pdo->execute();
        return $this->pdo->lastInserId();
    }

    //-------------------------------------------
    public function addProductCommande($data) {
        $this->pdo->prepare("INSERT INTO `product_command`(`id_command`, `id_product`, `quantity`) VALUES (:id_c, :id_p, :qtt)");
        $this->pdo->bind(':id_p', $data['id_product']);
        $this->pdo->bind(':id_c', $data['id_commande']);
        $this->pdo->bind(':qtt', $data['quantite']);
        if ($this->pdo->execute()){
            return true;
        } else {
            return false;
        }
    }

    //-------------------------------------------
    public function finishCommande() {
        return $this->pdo->commit();
    }

    //--------------------------------------------
    public function getCommands($user_id){
        $query = "SELECT * FROM `commandes` WHERE `user_id` = :user";
        $this->pdo->prepare($query);
        $this->pdo->bind(':user', $user_id);
        $command = $this->pdo->resultSet();
        return $command;
    }

    //--------------------------------------------
    public function getPendingCommands(){
        $query = "SELECT * FROM `commandes` where `status` = 'pending' ";
        $this->pdo->prepare($query);
        $pending = $this->pdo->resultSet();
        return $pending;
    }

    //--------------------------------------------
    public function getAcceptedCommands(){
        $query = "SELECT * FROM `commandes` where `status` = 'accepted'";
        $this->pdo->prepare($query);
        $accepted = $this->pdo->resultSet();
        return $accepted;
    }

    //--------------------------------------------
    public function accept($data){
        $query = "  UPDATE `commandes`
                    SET `shipping_date` = :shDate, `status` = 'Accepted'
                    WHERE id = :id";

        $this->pdo->prepare($query);
        $this->pdo->bind(':shDate', $data['shipping_date']);
        $this->pdo->bind(':id', $data['id']);
        $this->pdo->execute();
        
    }

    //--------------------------------------------
    public function reject($id){
        $query = "DELETE FROM `commandes` WHERE  `id`= :id";
        $this->pdo->prepare($query);
        $this->pdo->bind(':id',$id);
        $this->pdo->execute();

    }

    //-------------------------------------------
    public function totalPrice($id) {
        $this->pdo->prepare("SELECT SUM(p.sellP * pc.quantity) as price FROM product_command pc JOIN products p ON p.id = pc.id_product JOIN commandes c ON c.id = pc.id_command WHERE c.id = :id GROUP BY id_command");
        $this->pdo->bind(':id', $id);
        $row = $this->pdo->single();
        return $row;
    }

    //-------------------------------------------
    public function updatePrice($id,$total) {
        
        $query = "  UPDATE `commandes`
                    SET `total_price` = :ttl
                    WHERE `commandes`.id = :id ";
        $this->pdo->prepare($query);
        $this->pdo->bind(':ttl', $total->price);
        $this->pdo->bind(':id', $id);
        
        $this->pdo->execute();
    }
//-------------------------------------------
    public function facture($id){
        $query = "SELECT `products`.label, `products`.sellP, `commandes`.`id`,`commandes`.`total_price`, quantity FROM `product_command` INNER JOIN products ON products.id = product_command.id_product INNER JOIN commandes ON commandes.id = product_command.id_command WHERE id_command = :id";
        $this->pdo->prepare($query);
        $this->pdo->bind(':id', $id);
        $return = $this->pdo->resultSet();
        return $return;
    }
    
    //-------------------------------------------
    public function clearPanier() {
        $this->pdo->prepare("DELETE FROM cart");
        $this->pdo->execute();
    }

    //-------------------------------------------
    public function getUser($email){
        $query = "SELECT id FROM users WHERE email = :email";
        $this->pdo->prepare($query);
        $this->pdo->bind(':email', $email);
        $id = $this->pdo->single();
        return $id;
    }

    //-------------------------------------------
    public function getAllUsers(){
        $query = "SELECT * FROM users";
        $this->pdo->prepare($query);
        $users = $this->pdo->resultSet();
        return $users;
    }

    //-------------------------------------------
    public function getProduct($name){
        $query = "SELECT * FROM cart WHERE id_product = (SELECT id FROM products WHERE label = :namee)";
        $this->pdo->prepare($query);
        $this->pdo->bind(':namee', $name);
        $resp = $this->pdo->resultSet();
        return $resp;
    }
    //-------------------------------------------
    public function updateQtt($qtt,$name) {
        
        $query = "  UPDATE `cart`
                    SET `quantity` = :qtt
                    WHERE id_product = (SELECT id FROM products WHERE label = :namee) ";

        $this->pdo->prepare($query);
        $this->pdo->bind(':qtt', $qtt);
        $this->pdo->bind(':namee', $name);
        
        $this->pdo->execute();
    }
}


?>