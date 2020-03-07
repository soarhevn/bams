<html>
<head>
    <title>Form</title>
</head>
<body>

    <?php 

        $name = $_POST['name'];
        $age = $_POST['age'];

        echo "Welcome $name.<br/>You are $age years old."; 
     ?>
    
    <form action="" method="post">
    <div>
        Name: <input type="text" name="name" />
        Age: <input type="text" name="age" />

        <input type="submit" value="send" name="submit" />
    </div>
    </form>

	    
</body>
</html>