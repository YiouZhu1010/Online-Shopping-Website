<?php 


ini_set("file_uploads", "On");

include_once('session.php');
include_once('csrf.php');
include_once("db-connection.php");
$db = dbConnect();
session_start();
$checkSession = validSession();
if($checkSession['result']==false||$_SESSION['auth']['admin']==0){
    echo '<script>alert("Please log in as an Admin!");window.location.href="login.php"</script>';
    exit();
} 

    // generate all categories
    $sql1 = "SELECT * FROM categories";
    $result1 = $db->query($sql1);
    $categories = $result1->fetchAll();

    // generate all products
    $sql2 = "SELECT * FROM products";
    $result2 = $db->query($sql2);
    $products = $result2->fetchAll();

    $image_path = "/var/www/html/img/";
    $tableContent='';


    $add_cat_name = $edit_cat_name = $edit_cat_select = $delete_catid = $add_prod_name = $add_prod_catid = $add_prod_price = $add_prod_description = $add_prod_image = $edit_prod_pid = $edit_prod_name = $edit_prod_catid = $edit_prod_price = $edit_prod_description = $edit_prod_image = "";

    $add_cat_err = $edit_cat_err = $add_prod_name_err = $add_prod_price_err = $add_prod_description_err = $add_prod_image_err = $edit_prod_err = $edit_prod_pid_err = $edit_prod_name_err = $edit_prod_catid_err = $edit_prod_price_err = $edit_prod_description_err = $edit_prod_image_err = $delete_pid_err = $add_prod_catid_err = $delete_catid_err = $edit_cat_select_err = "";

    if ($_SERVER["REQUEST_METHOD"] == "POST") {

        if(isset($_POST['addCategory'])&&csrf_verifyNonce('addCategory',$_POST['addCategory'])) {
            $add_cat_name = testInput($_POST['add_cat_name']);
            if (empty($add_cat_name)) {
                $add_cat_err = "Category name is required.<br>";}
            elseif (strlen($add_cat_name) > 20) {
                $add_cat_err = "Please keep category name within 20 characters.<br>";}
            else {
                $stmt=$db->prepare('INSERT INTO categories (name) VALUES (:name)');
                $stmt->bindParam(':name',$add_cat_name);
                $stmt->execute();
                $add_cat_name = "";
            }
        }
        if(isset($_POST['editCategory'])&&csrf_verifyNonce('editCategory',$_POST['editCategory'])) {
            if (!isset($_POST['edit_cat_select'])) {
                $edit_cat_select_err = "Please select a category."; exit();
            } else {
                $edit_cat_select = $_POST['edit_cat_select'];
            }

            $edit_cat_name = testInput($_POST['edit_cat_name']);
            if (empty($edit_cat_name)) {
                $edit_cat_err = "Please enter a category name.<br>";}

            else if (strlen($edit_cat_name) > 20) {
                $edit_cat_err = "Please keep category name within 20 characters.<br>";
            }
            else {
                $stmt=$db->prepare('SELECT * FROM categories WHERE catid=(:catid)');
                $stmt->bindParam(':catid',$_POST['edit_cat_select']);
                $stmt->execute();
                $cat_name = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($cat_name['name'] == $edit_cat_name) {
                    $edit_cat_err = "New category name cannot be the same.<br>";
                }
                else {
                    $stmt=$db->prepare('UPDATE categories SET name=:name WHERE catid=:catid');
                    $stmt->bindParam(':name',$edit_cat_name);
                    $stmt->bindParam(':catid',$_POST['edit_cat_select']);
                    $stmt->execute();
                    $edit_cat_select = $edit_cat_name = "";
                }
            }
        }

        if(isset($_POST['deleteCategory'])&&csrf_verifyNonce('deleteCategory',$_POST['deleteCategory'])) {
            if (!isset($_POST['delete_catid'])) {
                $delete_catid_err = "Please select a category.<br>";

            } else {
                $stmt=$db->prepare('DELETE FROM categories WHERE catid=:catid');
                $stmt->bindParam(':catid',$_POST['delete_catid']);
                try {
                    $stmt->execute();
                }
                catch(Exception $e){
                    $delete_catid = $_POST['delete_catid'];
                    $delete_catid_err = "The category cannot be deleted until you remove all products under this category.";
                }}
        }

        if(isset($_POST['addProd'])&&csrf_verifyNonce('addProd',$_POST['addProd'])) {
            $has_error = false;

            // product name
            $add_prod_name = testInput($_POST['add_prod_name']);
            if (empty($add_prod_name)) {
                $add_prod_name_err = "Please enter category name.<br>";
                $has_error = true;

            } elseif (strlen($add_prod_name) > 20) {
                $add_prod_name_err = "Product name should not exceed 20 characters.<br>";
                $has_error = true;

            }

            if (!isset($_POST['add_prod_catid'])) {
                $has_error = true;
                $add_prod_catid_err = "Please select a category.<br>";

            } else {
                $add_prod_catid = $_POST['add_prod_catid'];
            }

            // price
            $add_prod_price = testInput($_POST['add_prod_price']);
            if (empty($add_prod_price)) {
                $add_prod_price_err = "Please enter a price.<br>";
                $has_error = true;

            } elseif (!is_numeric($add_prod_price)) {
                $add_prod_price_err = "Please make the price numeric.<br>";
                $has_error = true;

            } else {
                $add_prod_price =floatval($add_prod_price);
            }

            // description
            $add_prod_description = testInput($_POST['add_prod_description']);
            if (empty($add_prod_description)) {
                $add_prod_description_err = "Please enter a description. <br>";
                $has_error = true;

            } elseif (strlen($add_prod_description) > 2000) {
                $add_prod_description_err = "Description should not exceed 2000 characters.<br>";
                $has_error = true;
            }


            // image
            $image_ext = pathinfo(basename($_FILES['add_prod_image']['name']), PATHINFO_EXTENSION);
            if (empty($_FILES["add_prod_image"]["name"])) {
                $add_prod_image_err = "Please upload a file.<br>";
                $has_error = true;

            } elseif ($image_ext != 'jpg' && $image_ext != 'gif' && $image_ext != 'png') {
                $add_prod_image_err = "Image format must be jpg/gif/png.<br>";
                $has_error = true;

            } elseif ($_FILES['add_prod_image']['size'] > 10485760) { // check file size
                $add_prod_image_err = "Image size should not exceed 10MB.<br>";
                $has_error = true;
            }

            if (!$has_error) {
                 $stmt=$db->prepare('INSERT INTO products (catid, name, price, description) VALUES (:catid,:name,:price,:description)');
                    $stmt->bindParam(':catid',$_POST['add_prod_catid']);
                    $stmt->bindParam(':name',$add_prod_name);
                    $stmt->bindParam(':price',$add_prod_price);
                    $stmt->bindParam(':description',$add_prod_description);
                    $stmt->execute();
                $lastId = $db->lastInsertId();
                $image_name = $lastId.".".$image_ext;
                $image_file = $image_path.$image_name;
                move_uploaded_file($_FILES["add_prod_image"]["tmp_name"], $image_file);
                $stmt=$db->prepare('UPDATE products SET image=:image WHERE pid=:pid');
                $stmt->bindParam(':image',$image_name);
                $stmt->bindParam(':pid',$lastId);
                $stmt->execute();
                $add_prod_name = $add_prod_price = $add_prod_catid = $add_prod_description = "";
            }
        }

        if(isset($_POST['deleteProd'])&&csrf_verifyNonce('deleteProd',$_POST['deleteProd'])) {
            if (!isset($_POST['delete_pid'])) {
                $delete_pid_err = "Please select a product.<br>";

            } else {
                $stmt=$db->prepare('SELECT * FROM products WHERE pid=:pid');
                $stmt->bindParam(':pid',$_POST['delete_pid']);
                $stmt->execute();
                $delete_path_temp = $stmt->fetch(PDO::FETCH_ASSOC);
                $delete_path = $image_path.$delete_path_temp['image'];
                unlink($delete_path);
                $stmt=$db->prepare('DELETE FROM products WHERE pid=:pid');
                $stmt->bindParam(':pid',$_POST['delete_pid']);
                $stmt->execute();
            }
        }

        if(isset($_POST['editProd'])&&csrf_verifyNonce('editProd',$_POST['editProd'])) {

                $has_error = false;

                if (!isset($_POST['edit_prod_pid'])) {
                    $edit_prod_pid_err = "Please select a product.<br>";
                    $has_error = true;
                }
                else{
                $edit_prod_pid = $_POST['edit_prod_pid'];}


                $edit_prod_name = testInput($_POST['edit_prod_name']);

                if (empty($_POST['edit_prod_name']) && !isset($_POST['edit_prod_catid']) && empty($_POST['edit_prod_price']) && !isset($_POST['edit_prod_description']) && empty($_FILES["edit_prod_image"]["name"])) {
                    $edit_prod_err = "Please enter the required information.<br>";
                    $has_error = true;
                }

                if (strlen($edit_prod_name) > 20) {
                    $has_error = true;
                    $edit_prod_name_err = "Product name should not exceed 20 characters. <br>";
                }

                 $edit_prod_price = testInput($_POST['edit_prod_price']);
                if (!empty($edit_prod_price) && !is_numeric($edit_prod_price)) {
                    $has_error = true;
                    $edit_prod_price = "Please keep the price numeric. <br>";

                } else {
                    $edit_prod_price = floatval($edit_prod_price);

                }
                if (isset($_POST['edit_prod_description'])){
                $edit_prod_description = testInput($_POST['edit_prod_description']);}

                if (strlen($edit_prod_description) > 2000 && !empty($_POST['edit_prod_description'])) {
                    $has_error = true;
                    $edit_prod_description_err = "Description should not exceed 2000 characters. <br>";

                }

                if (!empty($_FILES["edit_prod_image"]["name"])) {
                    $image_ext = pathinfo(basename($_FILES['edit_prod_image']['name']), PATHINFO_EXTENSION);

                    if ($image_ext != 'jpg' && $image_ext != 'gif' && $image_ext != 'png') {
                        $has_error = true;
                        $edit_prod_image_err = "Image must be in jpg/gif/png format.<br>";

                    } elseif ($_FILES["edit_prod_image"]["size"] > 10485760) {
                        $has_error = true;
                        $edit_prod_image_err = "Image size should not exceed 10MB.<br>";

                    }

                }

                if (!$has_error) {
                    if (!empty($_POST['edit_prod_name'])) {
                        $edit_prod_name = testInput($_POST['edit_prod_name']);
                        $stmt=$db->prepare('UPDATE products SET name=:name WHERE pid=:pid');
                        $stmt->bindParam(':name',$edit_prod_name);
                        $stmt->bindParam(':pid',$_POST['edit_prod_pid']);
                        $stmt->execute();
                        $edit_prod_name = "";

                    }

                    if (isset($_POST['edit_prod_catid'])) {
                        $stmt=$db->prepare('UPDATE products SET catid=:catid WHERE pid=:pid');
                        $stmt->bindParam(':catid',$_POST['edit_prod_catid']);
                        $stmt->bindParam(':pid',$_POST['edit_prod_pid']);
                        $stmt->execute();
                        $edit_prod_catid = "";
                    }

                    if (!empty($_POST['edit_prod_price'])) {
                        $edit_prod_price = testInput($_POST['edit_prod_price']);
                        $stmt=$db->prepare('UPDATE products SET price=:price WHERE pid=:pid');
                        $stmt->bindParam(':price',$edit_prod_price);
                        $stmt->bindParam(':pid',$_POST['edit_prod_pid']);
                        $stmt->execute();
                        $edit_prod_price = "";
                    }

                    if (!empty($_POST['edit_prod_description'])) {
                        $edit_prod_description = testInput($_POST['edit_prod_description']);
                        $stmt=$db->prepare('UPDATE products SET description=:description WHERE pid=:pid');
                        $stmt->bindParam(':description',$edit_prod_description);
                        $stmt->bindParam(':pid',$_POST['edit_prod_pid']);
                        $stmt->execute();
                        $edit_prod_description = "";

                    }

                    if (!empty($_FILES["edit_prod_image"]["name"])) {
                        $stmt=$db->prepare('SELECT * FROM products WHERE pid=:pid');
                        $stmt->bindParam(':pid',$_POST['edit_prod_pid']);
                        $stmt->execute();
                        $delete_path_temp = $stmt->fetch(PDO::FETCH_ASSOC);
                        $delete_path = $image_path.$delete_path_temp['image'];
                        unlink($delete_path);
                        $image = $delete_path_temp['pid'].".".$image_ext;
                        $image_file = $image_path. $image;
                        move_uploaded_file($_FILES["edit_prod_image"]["tmp_name"], $image_file);

                        $stmt=$db->prepare('UPDATE products SET image=:image WHERE pid=:pid');
                        $stmt->bindParam(':image',$image);
                        $stmt->bindParam(':pid',$_POST['edit_prod_pid']);
                        $stmt->execute();
                    }
                    $edit_prod_pid = "";

                }
        }
    }
//generate order table
    $db=dbConnect();
    $stmt=$db->prepare("SELECT * FROM orders ORDER BY oid DESC");
    $stmt->execute();
    $result1=$stmt->fetchAll();
    foreach($result1 as $info){
        $tableContent.='<tr><td>'.$info['oid'].'</td>';
        $tableContent.='<td>'.$info['txn_id'].'</td>';
        $tableContent.='<td>'.$info['user'].'</td><td>';
        $st=$db->prepare('SELECT * FROM cart WHERE oid=:oid');
        $st->bindParam(':oid',$info['oid']);
        $st->execute();
        $produ=$st->fetchAll();
        foreach($produ as $value){
            $stmt=$db->prepare('SELECT name FROM products WHERE pid=:pid');
            $stmt->bindParam(':pid',$value['pid']);
            $stmt->execute();
            $product=$stmt->fetchAll();
            $tableContent .='name: '.$product[0][0].", quantity: ".$value['quantity']."; ";
        }
        $tableContent .='</td><td>';
        if($info['payment_status']==0){
            $tableContent .= "Incomplete</td><td>".$info['currency'].$info['sum']."</td></tr>";
        } else{
            $tableContent .= "Complete</td><td>".$info['currency'].$info['sum']."</td></tr>";
        }

    
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-type" content="text/html; charset=utf-8" />
    <title>IERG4210 Shop - Admin Panel</title>
    <link href="incl/admin.css" rel="stylesheet" type="text/css"/>
</head>
</html>
<body>
<h1>IERG4210 Shop - Admin Panel</h1>
<a href="home.php"> Home Page </a><br><br>
<article id="main">
<div id="categoryAdmin">
<section id="categoryPanel">
    <fieldset>
        
       
        <legend>New Category</legend>
        <form id="cat_insert" method="POST" action="admin.php?action=cat_insert">
         <input type="hidden" name="addCategory" value='<?php echo csrf_getNonce('addCategory'); ?>'/>
            <label>Name</label>
            <div><input type="text" name="add_cat_name" value="<?php echo $add_cat_name; ?>" required="true" pattern="^[\w\- ]+$" /></div>
            <span class="er_message"><?php echo $add_cat_err;?></span>
            <input type="submit" value="Add" />
        </form>
    </fieldset>
    <ul id="categoryList"></ul>
</section>
<br>
<section id="categoryEditPanel">
    <fieldset>
        
        
        <legend>Editing Category</legend>
        <form id="cat_edit" method="POST" action="admin.php?action=cat_edit">
        <input type="hidden" name="editCategory" value='<?php echo csrf_getNonce('editCategory'); ?>'/>
            <label>Old Name *</label>
            <select name="edit_cat_select" required>
                <option disabled="disabled" selected="selected">-- select a category --</option>
                <?php                
                foreach ($categories as $value) {
                     if ($edit_cat_select == $value['catid']) {
                        echo '<option selected value='.$value['catid'].'>'.$value['name'].'</option>';
                     } else {
                        echo '<option value='.$value['catid'].'>'.$value['name'].'</option>';
                     }
                }
                ?>
            </select>
            <span class="er_message"><?php echo $edit_cat_select_err;?></span>
            <label for="cat_edit_name">New Name *</label>
            <div><input  type="text" name="edit_cat_name" required="true" value="<?php echo $edit_cat_name ?>" pattern="^[\w\- ]+$" /></div>
            <span class="er_message"><?php echo $edit_cat_err;?></span>
            <input type="hidden" id="cat_edit_catid" name="catid" />
            <input type="submit" value="Submit" /> <input type="button" id="cat_edit_cancel" value="Cancel" />
        </form>
    </fieldset>
</section>
<br><br>
 <section id="categoryRemovePanel">
        <fieldset>
            <legend>Delete a Category</legend>
            <form id="cat_remove" method="POST" enctype="multipart/form-data" action="admin.php?action=cat_delete" >
            <input type="hidden" name="deleteCategory" value='<?php echo csrf_getNonce('deleteCategory'); ?>'/>
                <label>Category *</label>
                <select name="delete_catid">
                    <option disabled="disabled" selected="selected">-- select a category--</option>
                    <?php
                    foreach ($categories as $value) {
                        if ($delete_catid == $value['catid']) {
                            echo '<option selected value='.$value['catid'].'>'.$value['name'].'</option>';
                        }
                        else {
                            echo '<option value='.$value['catid'].'>'.$value['name'].'</option>';
                        }
                    }
                    ?>
                </select>
                <span class="er_message"><?php echo $delete_catid_err;?></span>
                <br><br>
                <input type="Submit" value="Delete" />
            </form>
        </fieldset>
    </section>
</div>

<div id="column2">
<section id="productPanel">
    <fieldset class="productField">
        <legend>New Product</legend>
        <form id="prod_insert" method="POST" action="admin.php?action=prod_insert" enctype="multipart/form-data">
        <input type="hidden" name="addProd" value='<?php echo csrf_getNonce('addProd'); ?>' />
            <label>Category *</label>
            <div><select name="add_prod_catid">
                <option disabled selected>-- select a category --</option>
                <?php
                foreach ($categories as $value) {
                    if ($add_prod_catid == $value['catid']) {
                        echo '<option selected value='.$value['catid'].'>'.$value['name'].'</option>';
                    } else {
                        echo '<option value='.$value['catid'].'>'.$value['name'].'</option>';
                    }
                }
                ?> 
            </select>
            <span class="er_message"><?php echo $add_prod_catid_err; ?></span></div>
            <label>Name *</label>
            <div><input type="text" name="add_prod_name" value="<?php echo $add_prod_name; ?>" required="true" pattern="^[\w\- ]+$" /></div>
            <span class="er_message"><?php echo $add_prod_name_err; ?></span>

            <label>Price *</label>
            <div><input id="prod_insert_price" type="number" name="add_prod_price" value="<?php echo $add_prod_price; ?>" required="true" pattern="^[\d\.]+$" /></div>
            <span class="er_message"><?php echo $add_prod_price_err; ?></span>

            <label>Description</label>
            <div><textarea name="add_prod_description" pattern="^[\w\-, ]$"><?php echo $add_prod_description; ?></textarea></div>
            <span class="er_message"><?php echo $add_prod_description_err; ?></span>

            <label>Image *</label>
            <div><input type="file" accept=".jpg, .gif, .png" name="add_prod_image" required/></div>
            <span class="er_message"><?php echo $add_prod_image_err; ?></span>

            <input type="submit" value="Submit" />
        </form>
    </fieldset>
</section>  
<br>

<section id="productRemovePanel">
    <fieldset>
        <legend>Remove a Product</legend>
        <form id="prod_remove" method="POST" action="admin.php?action=prod_remove" >
        <input type="hidden" name="deleteProd" value='<?php echo csrf_getNonce('deleteProd'); ?>' />
            <label>Name *</label>
            <select name="delete_pid">
                <option disabled selected>-- select a product --</option>
                <?php
                foreach ($products as $value) {
                    echo "<option value=".$value["pid"].">".$value["name"]."</option>";
                }
                ?>
            </select>
            <span class="er_message"> <?php echo $delete_pid_err; ?></span>
            <br><br>
            <input type="submit" value="Delete" />
        </form>
    </fieldset>
</section>
</div>
<br>

<section id="productEditPanel">
     <fieldset class="productField">
        <legend>Edit a Product</legend>
        <form id="prod_edit" method="POST" enctype="multipart/form-data" action="admin.php?action=prod_edit">
        <input type="hidden" name="editProd" value='<?php echo csrf_getNonce('editProd'); ?>' />
            
            <label class="productLabel">Select a product *</label>
            <div><select name="edit_prod_pid" required>
                <option disabled selected> -- select a product -- </option>
                <?php
                foreach ($products as $value) {
                    if ($edit_prod_pid == $value["pid"]) {
                        echo "<option selected value=".$value["pid"].">".$value["name"]."</option>";

                    } else {
                        echo "<option value=".$value["pid"].">".$value["name"]."</option>";
                    }
                }
                ?>
            </select></div>
            <span class="er_message"> <?php echo $edit_prod_pid_err ?></span>

            <label class="productLabel">New product name </label>
            <div><input type="text" name="edit_prod_name" value="<?php echo $edit_prod_name ?>" /></div>
             <span class="er_message"> <?php echo $edit_prod_name_err ?></span>


            <label class="productLabel">Select a new category</label>
            <div><select name="edit_prod_catid">
                <option disabled selected> -- select a category -- </option>
                <?php
                foreach ($categories as $value) {
                    if ($edit_prod_catid == $value['catid']) {
                        echo '<option selected value='.$value['catid'].'>'.$value['name'].'</option>';

                    } else {
                        echo '<option value='.$value['catid'].'>'.$value['name'].'</option>';

                    }
                }
                ?>
            </select></div>
            <span class="er_message"> <?php echo $edit_prod_catid_err ?></span>
         
            <label class="productLabel">New price </label>
            <div><input type="number" step=any name="edit_prod_price" value="<?php echo $edit_prod_price ?> " /></div>
            <span class="er_message"> <?php echo $edit_prod_price_err ?> </span>
            <br>

            <label class="productLabel">New description </label>
            <div><textarea name="edit_prod_description" ><?php echo $edit_prod_description ?></textarea></div>
            <span class="er_message"> <?php echo $edit_prod_description_err; ?></span>
          
            <label class="productLabel">New image file (jpg/gif/png)  </label>
            <div><input type="file" accept=".jpg,.gif,.png" name="edit_prod_image" /></div>
            <span class="er_message"> <?php echo $edit_prod_image_err; ?></span>
            <input type="submit" name="submit" value="Update" />
            <span class="er_message"> <?php echo $edit_prod_err ?></span>
            <br>
        </form>
    </fieldset>

</section>

</article>
<br><br>
<table>
    <tr>
        <th>Order ID</th>
        <th>Transaction ID</th>
        <th>User</th>
        <th>Product List</th>
        <th>Payment Status</th>
        <th>Sum</th>
    </tr>
    <?php echo $tableContent;?>
</table>
</body>
</html>