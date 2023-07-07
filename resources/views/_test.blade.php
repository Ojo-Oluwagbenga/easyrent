<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>  
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <title>Document</title>
</head>
<body>
    Hey
    <input type='file' id="up_file" name='file'>
    <div class="init"></div> 
    <style>
        .init{
            width: 20px;
            height: 20px;
            background: green;
        }
    </style>

    <script>
        let addedfile = {};
        let resaddcount = 1;
        let data = {}

        $('#up_file').on('input', function() {
            let fileobj = $('#up_file')[0].files[0];
            if (typeof(fileobj) !== 'undefined'){
                if (fileobj.size < 10097152){
                    resaddcount += 1;
                    let name = fileobj.name;
                    addedfile['resadd'+resaddcount] = [fileobj, fileobj.name];
                }else{
                    alert('File is too large');
                }       
            }else{
                console.log(typeof($('#file')[0]));
            }
        });

        $(".init").click(function(){
            var fd = new FormData();
            
            let count = 0;
            for (const key in addedfile) {
                const file = addedfile[key][0];
                count += 1;
                fd.append('file-'+ (count), file);          
            }

            data = {
                'name' : "ssaa",
                'price' : 10,
                'description' : "['required', 'min:100']",
                'code' : "['required']",
                'type' : "['required']",
                'category' : "['required']",
                'imagepaths' : "['required']",
            }

            fd.append('createset',JSON.stringify(data));
            fd.append('number_of_images', count);
            
            
            axios({
                    method: 'POST',
                    url: './apis/product/create',
                    headers: {
                        'Cache-Control': 'no-cache',
                        'Pragma': 'no-cache',
                        "X-CSRF-TOKEN" : '{{csrf_token()}}'
                    },        
                    data:fd            
            }).then(response => {
                console.log(response);
            })
                .catch(error => console.error(error))
        })
    </script>
</body>
</html>