<?php
require_once __DIR__.'/../config/config.php';
require_once __DIR__.'/gestion.php';
$menu = obtenerMenu($_SESSION['documento']);
?>
<div class="sidebar close">
    <header>
        <div class="image-text">
            <span class="image">
            <a href="../Inicio"><img src="../libs/img/Logo128.png" alt="logo"></a>
            </span>
            <div class="text header-text">
                <span class="name"><?php echo APP; ?></span>
                <span class="profession">GROUP S&S</span>
            </div>
        </div>
        <i class="fa-solid fa-angle-right toggle"></i>
    </header>
    <div class="menu-bar">
        <div class="menu">
        <div class="profile clearfix">
              <div class="profile_pic" style="display: none;">
                <img src="https://colorlib.com/polygon/gentelella/images/img.jpg" alt="..." class="img-circle profile_img">
              </div>
              <div class="profile_info" style="display: none;">
                <span><?php $user = explode(" ",$_SESSION['nombre']);echo($user[0]);?></span>
                <h2><?php echo($user[2]);?></h2>
              </div>
            </div>

            <!-- <div class='name'></div> -->
            <li class="search-box">
                <i class="fa-solid fa-magnifying-glass icon"></i>
                <input id="search" type="search" placeholder="Buscar . . .">
            </li>
            <ul class="menu-links">
                <?php foreach ($menu as $item): ?>
                    <li class="nav-link" title="<?php echo htmlspecialchars($item['link']); ?>">
                        <a href="<?php echo htmlspecialchars($item['enlace']); ?>" class="main-item <?php echo isset($item['submenu']) ? 'has-submenu' : ''; ?>">
                            <i class="<?php echo htmlspecialchars($item['icono']); ?> icon"></i>
                            <span class="text nav-text"><?php echo htmlspecialchars($item['link']); ?></span>
                            <?php if (isset($item['submenu'])): ?>
                                <i class="fa-solid fa-chevron-down submenu-arrow"></i>
                            <?php endif; ?>
                        </a>
                        <?php if (isset($item['submenu'])): ?>
                            <ul class="sub-menu">
                                <?php foreach ($item['submenu'] as $subitem): ?>
                                    <li class="nav-link" title="<?php echo htmlspecialchars($item['link']); ?>">
                                        <a href="<?php echo htmlspecialchars($subitem['enlace']); ?>">
                                            <i class="<?php echo htmlspecialchars($subitem['icono']); ?> icon"></i>
                                            <span class="text nav-text"><?php echo htmlspecialchars($subitem['link']); ?></span>
                                        </a>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
        <div class="bottom-content">
            <li>
                <a href="../../logout.php">
                    <i class="fa-solid fa-arrow-right-from-bracket icon"></i>
                    <span class="text nav-text">Cerrar Sesión</span>
                </a>
            </li>
            <li class="mode">
                <div class="moon-sun">
                    <i class="fa fa-moon icon moon"></i>
                    <i class="fa fa-sun icon sun"></i>
                </div>
                <span class="mode-text text">Oscuro</span>
                <div class="toggle-switch">
                    <span class ="switch"></span>
                </div>
            </li>
        </div>
    </div>
</div>