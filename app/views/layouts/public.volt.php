<div class="navbar">
    <div class="navbar-inner">
      <div class="container" style="width: auto;">
        <a class="btn btn-navbar" data-toggle="collapse" data-target=".nav-collapse">
          <span class="icon-bar"></span>
          <span class="icon-bar"></span>
          <span class="icon-bar"></span>
        </a>
        <?= $this->tag->linkTo([null, 'class' => 'brand', 'Vökuró']) ?>
        <div class="nav-collapse">
          <ul class="nav"><?php $menus = ['Home' => 'index', 'About' => 'about']; ?><?php foreach ($menus as $key => $value) { ?>
              <?php if ($value == $this->dispatcher->getControllerName()) { ?>
              <li class="active"><?= $this->tag->linkTo([$value, $key]) ?></li>
              <?php } else { ?>
              <li><?= $this->tag->linkTo([$value, $key]) ?></li>
              <?php } ?><?php } ?></ul>

          <ul class="nav pull-right"><?php if (isset($logged_in) && !(empty($logged_in))) { ?><li><?= $this->tag->linkTo(['users', 'Users Panel']) ?></li>
            <li><?= $this->tag->linkTo(['session/logout', 'Logout']) ?></li>
            <?php } else { ?>
            <li><?= $this->tag->linkTo(['session/login', 'Login']) ?></li>
            <?php } ?>
          </ul>
        </div><!-- /.nav-collapse -->
      </div>
    </div><!-- /navbar-inner -->
  </div>

<div class="container main-container">
  <?= $this->getContent() ?>
</div>

<footer>
Made with love by the Phalcon Team

    <?= $this->tag->linkTo(['privacy', 'Privacy Policy']) ?>
    <?= $this->tag->linkTo(['terms', 'Terms']) ?>

© <?= date('Y') ?> Phalcon Team.
</footer>
