<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Relay</title>
    <link rel="stylesheet" href="style.css">
    <script src="ajax.js"></script>
    <style>
    .flex-container {
        display: flex;
        flex-direction: row;
        padding: 2px 0;
        margin-bottom: 10px;
    }

    .flex-container > .flex-item {
        flex: auto;
    }

    .flex-container > .raw-item {
        width: 40px;
        padding-top: 4px;
        
    }

    .flex-container h4{
        margin: 0;
    }
    .flex-container p{
        margin: 0;
    }

    .switch {
        position: relative;
        display: inline-block;
    }
    .switch-input {
        display: none;
    }
    .switch-label {
        position: relative;
        display: block;
        width: 48px;
        height: 20px;
        text-indent: -150%;
        clip: rect(0 0 0 0);
        color: transparent;
        user-select: none;
    }
    .switch-label::before,
    .switch-label::after {
        content: "";
        display: block;
        position: absolute;
        cursor: pointer;
    }
    .switch-label::before {
        width: 100%;
        height: 100%;
        background-color: #dedede;
        border-radius: 9999em;
        -webkit-transition: background-color 0.25s ease;
        transition: background-color 0.25s ease;
    }
    .switch-label::after {
        top: 0;
        left: 0;
        width: 20px;
        height: 20px;
        border-radius: 50%;
        background-color: #fff;
        box-shadow: 0 0 2px rgba(0, 0, 0, 0.45);
        -webkit-transition: left 0.25s ease;
        transition: left 0.25s ease;
    }
    .switch-input:checked + .switch-label::before {
        background-color: #5bb926;
    }
    .switch-input:checked + .switch-label::after {
        left: 28px;
    }


    /*
    body {
        display: flex;
        justify-content: center;
        align-items: center;
        height: 100vh;
    }
    */
    </style>
    <script>
    function sendValue(id, value)
    {
        ajax.post("api.php", {gpio:id, value:value}, function(data){
        }, false);
    }
    window.onload = function(e){
        var sw = document.querySelectorAll('.switch');
        for(var i = 0; i<sw.length; i++)
        {
            sw[i].addEventListener('change', function(e2){
                sendValue(e2.target.getAttribute('id'), e2.target.checked?1:0);
            });
        }
    }
    
    </script>
</head>
<body>
    <div class="all">
        <h3>Relay</h3>      
        <div class="form-item">


            <div class="flex-container">
                <div class="flex-item">
                    <h4>Input 1</h4>
                    <p>Connected to GPIO 14</p>
                 </div>
                <div class="raw-item switch">
                    <input type="checkbox" class="switch-input" value="1" id="14" /><label class="switch-label" for="14">Toggle</label>
                </div>
            </div> 

            <div class="flex-container">
                <div class="flex-item">
                    <h4>Input 2</h4>
                    <p>Connected to GPIO 27</p>
                </div>
                <div class="raw-item switch">
                    <input type="checkbox" class="switch-input" value="1" id="27" /><label class="switch-label" for="27">Toggle</label>
                </div>
            </div> 

            <div class="flex-container">
                <div class="flex-item">
                    <h4>Input 3</h4>
                    <p>Connected to GPIO 26</p>
                 </div>
                <div class="raw-item switch">
                    <input type="checkbox" class="switch-input" value="1" id="26" /><label class="switch-label" for="26">Toggle</label>
                </div>
            </div> 

            <div class="flex-container">
                <div class="flex-item">
                    <h4>Input 4</h4>
                    <p>Connected to GPIO 25</p>
                </div>
                <div class="raw-item switch">
                    <input type="checkbox" class="switch-input" value="1" id="25" /><label class="switch-label" for="25">Toggle</label>
                </div>
            </div> 

            <div class="flex-container">
                <div class="flex-item">
                    <h4>Input 5</h4>
                    <p>Connected to GPIO 33</p>
                </div>
                <div class="raw-item switch">
                    <input type="checkbox" class="switch-input" value="1" id="33" /><label class="switch-label" for="33">Toggle</label>
                </div>
            </div> 

            <div class="flex-container">
                <div class="flex-item">
                    <h4>Input 6</h4>
                    <p>Connected to GPIO 32</p>
                 </div>
                <div class="raw-item switch">
                    <input type="checkbox" class="switch-input" value="1" id="32" /><label class="switch-label" for="32">Toggle</label>
                </div>
            </div> 


        </div>
    </div>
</body>
</html>