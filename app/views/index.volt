<!DOCTYPE html>
<html>
    <head>
        <title>Welcome to JxunCall</title>
        {{ stylesheet_link('public/css/interface.css') }}
        {{ javascript_include('public/js/jquery-3.3.1.min.js') }}
        {{ javascript_include('public/js/bootstrap.js') }}
        {{ javascript_include('public/js/interface.js') }}
    </head>
    <body>  

        {{ content() }}
    </body>
</html>
