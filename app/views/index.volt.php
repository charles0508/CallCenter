<!DOCTYPE html>
<html>
    <head>
        <title>Welcome to JxunCall</title>
        <?= $this->tag->stylesheetLink('public/css/interface.css') ?>
        <?= $this->tag->javascriptInclude('public/js/jquery-3.3.1.min.js') ?>
        <?= $this->tag->javascriptInclude('public/js/bootstrap.js') ?>
        <?= $this->tag->javascriptInclude('public/js/interface.js') ?>
    </head>
    <body>  

        <?= $this->getContent() ?>
    </body>
</html>
