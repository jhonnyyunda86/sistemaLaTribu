    </div><!-- /content-area -->

    <!-- ══════════════════════════════════════
         FOOTER — sticky bottom con flex
    ══════════════════════════════════════ -->
    <footer id="bottom-footer" style="background:#0c0a09;border-top:1px solid rgba(251,146,60,.15);">

        <!-- Franja principal -->
        <div style="padding:1.5rem 2rem;display:grid;grid-template-columns:repeat(3,1fr);gap:1.5rem;border-bottom:1px solid #1c1917;">

            <!-- Marca -->
            <div>
                <div style="display:flex;align-items:center;gap:.75rem;margin-bottom:.6rem;">
                    <div style="width:36px;height:36px;background:linear-gradient(135deg,#ea580c,#f59e0b);border-radius:10px;display:flex;align-items:center;justify-content:center;color:#fff;font-size:.9rem;flex-shrink:0;">
                        <i class="fa-solid fa-utensils"></i>
                    </div>
                    <span style="color:#fff;font-weight:900;font-size:1.1rem;">La Tribu</span>
                </div>
                <p style="color:#57534e;font-size:.75rem;line-height:1.5;">
                    Sistema de gestión para restaurante enfocado en pedidos, reservas, usuarios e inventario.
                </p>
            </div>

            <!-- Enlaces rápidos -->
            <div>
                <h4 style="color:#fff;font-weight:700;font-size:.8rem;margin-bottom:.75rem;display:flex;align-items:center;gap:.4rem;">
                    <i class="fa-solid fa-bolt" style="color:#ea580c;font-size:.7rem;"></i> Enlaces Rápidos
                </h4>
                <div style="display:flex;flex-direction:column;gap:.4rem;font-size:.75rem;">
                    <?php
                    $rol2 = $_SESSION['usuario']['role'] ?? 'cliente';
                    if ($rol2 === 'admin'): ?>
                        <a href="admin_dashboard.php" style="color:#a8a29e;text-decoration:none;display:flex;align-items:center;gap:.4rem;" onmouseover="this.style.color='#fb923c'" onmouseout="this.style.color='#a8a29e'">
                            <i class="fa-solid fa-chart-line" style="color:#ea580c;width:12px;"></i> Dashboard
                        </a>
                        <a href="admin_menu.php" style="color:#a8a29e;text-decoration:none;display:flex;align-items:center;gap:.4rem;" onmouseover="this.style.color='#fb923c'" onmouseout="this.style.color='#a8a29e'">
                            <i class="fa-solid fa-utensils" style="color:#ea580c;width:12px;"></i> Menú
                        </a>
                        <a href="admin_reservas.php" style="color:#a8a29e;text-decoration:none;display:flex;align-items:center;gap:.4rem;" onmouseover="this.style.color='#fb923c'" onmouseout="this.style.color='#a8a29e'">
                            <i class="fa-solid fa-calendar-check" style="color:#ea580c;width:12px;"></i> Reservas
                        </a>
                        <a href="admin_reportes.php" style="color:#a8a29e;text-decoration:none;display:flex;align-items:center;gap:.4rem;" onmouseover="this.style.color='#fb923c'" onmouseout="this.style.color='#a8a29e'">
                            <i class="fa-solid fa-chart-bar" style="color:#ea580c;width:12px;"></i> Reportes
                        </a>
                        <a href="admin_inventario.php" style="color:#a8a29e;text-decoration:none;display:flex;align-items:center;gap:.4rem;" onmouseover="this.style.color='#fb923c'" onmouseout="this.style.color='#a8a29e'">
                            <i class="fa-solid fa-boxes-stacked" style="color:#ea580c;width:12px;"></i> Inventario
                        </a>
                    <?php elseif ($rol2 === 'mesero'): ?>
                        <a href="mesero_dashboard.php" style="color:#a8a29e;text-decoration:none;display:flex;align-items:center;gap:.4rem;" onmouseover="this.style.color='#fb923c'" onmouseout="this.style.color='#a8a29e'">
                            <i class="fa-solid fa-clipboard-list" style="color:#ea580c;width:12px;"></i> Pedidos
                        </a>
                        <a href="admin_mesas.php" style="color:#a8a29e;text-decoration:none;display:flex;align-items:center;gap:.4rem;" onmouseover="this.style.color='#fb923c'" onmouseout="this.style.color='#a8a29e'">
                            <i class="fa-solid fa-chair" style="color:#ea580c;width:12px;"></i> Mesas
                        </a>
                    <?php else: ?>
                        <a href="cliente_dashboard.php" style="color:#a8a29e;text-decoration:none;display:flex;align-items:center;gap:.4rem;" onmouseover="this.style.color='#fb923c'" onmouseout="this.style.color='#a8a29e'">
                            <i class="fa-solid fa-burger" style="color:#ea580c;width:12px;"></i> Menú
                        </a>
                        <a href="cliente_reservas.php" style="color:#a8a29e;text-decoration:none;display:flex;align-items:center;gap:.4rem;" onmouseover="this.style.color='#fb923c'" onmouseout="this.style.color='#a8a29e'">
                            <i class="fa-solid fa-calendar-plus" style="color:#ea580c;width:12px;"></i> Reservar
                        </a>
                        <a href="cliente_historial.php" style="color:#a8a29e;text-decoration:none;display:flex;align-items:center;gap:.4rem;" onmouseover="this.style.color='#fb923c'" onmouseout="this.style.color='#a8a29e'">
                            <i class="fa-solid fa-clock-rotate-left" style="color:#ea580c;width:12px;"></i> Mis Compras
                        </a>
                        <a href="cliente_cuenta.php" style="color:#a8a29e;text-decoration:none;display:flex;align-items:center;gap:.4rem;" onmouseover="this.style.color='#fb923c'" onmouseout="this.style.color='#a8a29e'">
                            <i class="fa-solid fa-circle-user" style="color:#ea580c;width:12px;"></i> Mi Cuenta
                        </a>
                        <a href="cliente_historial.php" style="color:#a8a29e;text-decoration:none;display:flex;align-items:center;gap:.4rem;" onmouseover="this.style.color='#fb923c'" onmouseout="this.style.color='#a8a29e'">
                            <i class="fa-solid fa-clock-rotate-left" style="color:#ea580c;width:12px;"></i> Mis Compras
                        </a>
                    <?php endif; ?>
                    <a href="../../controllers/AuthController.php?accion=logout"
                       style="color:#a8a29e;text-decoration:none;display:flex;align-items:center;gap:.4rem;margin-top:.2rem;"
                       onmouseover="this.style.color='#f87171'" onmouseout="this.style.color='#a8a29e'">
                        <i class="fa-solid fa-right-from-bracket" style="color:#ef4444;width:12px;"></i> Cerrar sesión
                    </a>
                </div>
            </div>

            <!-- Sesión activa -->
            <div>
                <h4 style="color:#fff;font-weight:700;font-size:.8rem;margin-bottom:.75rem;display:flex;align-items:center;gap:.4rem;">
                    <i class="fa-solid fa-circle" style="color:#22c55e;font-size:.5rem;"></i> Sesión Activa
                </h4>
                <div style="display:flex;align-items:center;gap:.6rem;margin-bottom:.6rem;">
                    <div style="width:34px;height:34px;border-radius:50%;background:#ea580c;color:#fff;display:flex;align-items:center;justify-content:center;font-weight:900;font-size:.9rem;flex-shrink:0;">
                        <?= strtoupper(substr($nombre ?? 'U', 0, 1)) ?>
                    </div>
                    <div>
                        <p style="color:#fff;font-weight:700;font-size:.8rem;"><?= htmlspecialchars($nombre ?? '') ?></p>
                        <p style="color:#fb923c;font-size:.7rem;"><?= htmlspecialchars(ucfirst($rol ?? '')) ?></p>
                    </div>
                </div>
                <p style="color:#57534e;font-size:.72rem;display:flex;align-items:center;gap:.35rem;">
                    <i class="fa-solid fa-clock" style="color:#ea580c;"></i>
                    <?= date('d/m/Y H:i') ?>
                </p>
                <p style="color:#57534e;font-size:.72rem;display:flex;align-items:center;gap:.35rem;margin-top:.3rem;">
                    <i class="fa-solid fa-location-dot" style="color:#ea580c;"></i>
                    Restaurante La Tribu · Colombia
                </p>
            </div>

        </div>

        <!-- Franja copyright -->
        <div style="padding:.75rem 2rem;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:.5rem;">
            <span style="color:#44403c;font-size:.72rem;">
                © <?= date('Y') ?>
                <strong style="color:#ea580c;">La Tribu</strong>
                · Hecho con <i class="fa-solid fa-heart" style="color:#ef4444;"></i> para restaurantes colombianos
                · Todos los derechos reservados
            </span>
            <div style="display:flex;gap:.75rem;font-size:.9rem;">
                <i class="fa-brands fa-facebook" style="color:#44403c;cursor:pointer;transition:color .2s;"
                   onmouseover="this.style.color='#fb923c'" onmouseout="this.style.color='#44403c'"></i>
                <i class="fa-brands fa-instagram" style="color:#44403c;cursor:pointer;transition:color .2s;"
                   onmouseover="this.style.color='#fb923c'" onmouseout="this.style.color='#44403c'"></i>
                <i class="fa-brands fa-whatsapp" style="color:#44403c;cursor:pointer;transition:color .2s;"
                   onmouseover="this.style.color='#22c55e'" onmouseout="this.style.color='#44403c'"></i>
            </div>
        </div>

    </footer>

</div><!-- /col-right -->
</div><!-- /app -->
</body>
</html>
