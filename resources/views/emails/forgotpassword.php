<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mail Body</title>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
</head>
<body>
    Hey there. Kindly follow the link below to reset your password <br><br>
    <a href="https://ert-smoky.vercel.app/newpassword/{{ $data['link_addr'] }}"></a>

    <br>
    Or click the button below to reset your password
    <div class="cpass">Change Password</div>

    <style>
        .cpass{
            padding: 10px 20px;
            background-color:blue;
            margin: 10px 0px;
            border-radius: 10px;
            width: max-content;
        }
    </style>
    <script>
        $(".cpass").click(function(){
            window.location.href = "https://ert-smoky.vercel.app/newpassword/{{ $data['link_addr'] }}"
        });
    </script>
</body>

</html>