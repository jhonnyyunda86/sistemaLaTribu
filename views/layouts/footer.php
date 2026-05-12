    </div><!-- /content-area -->

    <!-- ══ FOOTER FIJO ABAJO ══ -->
    <footer id="bottom-footer" class="bg-stone-950 text-stone-400 border-t border-orange-500/20 flex-shrink-0">

        <!-- Franja principal -->
        <div class="px-8 py-5 grid grid-cols-1 sm:grid-cols-3 gap-6 border-b border-stone-800">

            <!-- Marca -->
            <div>
                <div class="flex items-center gap-3 mb-2">
                    <div class="w-9 h-9 bg-gradient-to-br from-orange-600 to-amber-500 rounded-xl flex items-center justify-center text-white">
                        <i class="fa-solid fa-utensils text-sm"></i>
                    </div>
                    <span class="text-white font-black text-lg">La Tribu</span>
                </div>
                <p class="text-xs text-stone-500 leading-relaxed">
                    Sistema de gestión para restaurante enfocado en pedidos, reservas, usuarios e inventario.
                </p>
            </div>

            <!-- Enlaces rápidos -->
            <div>
                <h4 class="text-white font-bold text-sm mb-3 flex items-center gap-2">
                    <i class="fa-solid fa-bolt text-orange-500 text-xs"></i> Enlaces Rápidos
                </h4>
                <div class="space-y-1.5 text-xs">
                    <?php
                    $rol2 = $_SESSION['usuario']['role'] ?? 'cliente';
                    if ($rol2 === 'admin'): ?>
                        <a href="admin_dashboard.php" class="flex items-center gap-2 hover:text-orange-400 transition"><i class="fa-solid fa-chart-line text-orange-600 w-3"></i> Dashboard</a>
                        <a href="admin_menu.php"      class="flex items-center gap-2 hover:text-orange-400 transition"><i class="fa-solid fa-utensils text-orange-600 w-3"></i> Menú</a>
                        <a href="admin_reservas.php"  class="flex items-center gap-2 hover:text-orange-400 transition"><i class="fa-solid fa-calendar-check text-orange-600 w-3"></i> Reservas</a>
                        <a href="admin_reportes.php"  class="flex items-center gap-2 hover:text-orange-400 transition"><i class="fa-solid fa-chart-bar text-orange-600 w-3"></i> Reportes</a>
                    <?php elseif ($rol2 === 'mesero'): ?>
                        <a href="mesero_dashboard.php" class="flex items-center gap-2 hover:text-orange-400 transition"><i class="fa-solid fa-clipboard-list text-orange-600 w-3"></i> Pedidos</a>
                        <a href="admin_mesas.php"      class="flex items-center gap-2 hover:text-orange-400 transition"><i class="fa-solid fa-chair text-orange-600 w-3"></i> Mesas</a>
                    <?php else: ?>
                        <a href="cliente_dashboard.php" class="flex items-center gap-2 hover:text-orange-400 transition"><i class="fa-solid fa-burger text-orange-600 w-3"></i> Menú</a>
                        <a href="cliente_reservas.php"  class="flex items-center gap-2 hover:text-orange-400 transition"><i class="fa-solid fa-calendar-plus text-orange-600 w-3"></i> Reservar</a>
                    <?php endif; ?>
                    <a href="../../controllers/AuthController.php?accion=logout" class="flex items-center gap-2 hover:text-red-400 transition mt-1">
                        <i class="fa-solid fa-right-from-bracket text-red-500 w-3"></i> Cerrar sesión
                    </a>
                </div>
            </div>

            <!-- Sesión activa -->
            <div>
                <h4 class="text-white font-bold text-sm mb-3 flex items-center gap-2">
                    <i class="fa-solid fa-circle text-green-500 text-xs animate-pulse"></i> Sesión Activa
                </h4>
                <div class="space-y-2 text-xs">
                    <div class="flex items-center gap-2">
                        <div class="w-8 h-8 rounded-full bg-orange-500 text-white flex items-center justify-center font-black text-sm flex-shrink-0">
                            <?= strtoupper(substr($nombre ?? 'U', 0, 1)) ?>
                        </div>
                        <div>
                            <p class="text-white font-bold"><?= htmlspecialchars($nombre ?? '') ?></p>
                            <p class="text-orange-400"><?= htmlspecialchars(ucfirst($rol ?? '')) ?></p>
                        </div>
                    </div>
                    <p class="text-stone-500 flex items-center gap-1.5 mt-2">
                        <i class="fa-solid fa-clock text-orange-600"></i>
                        <?= date('d/m/Y H:i') ?>
                    </p>
                    <p class="text-stone-500 flex items-center gap-1.5">
                        <i class="fa-solid fa-location-dot text-orange-600"></i>
                        Restaurante La Tribu · Colombia
                    </p>
                </div>
            </div>

        </div>

        <!-- Franja copyright -->
        <div class="px-8 py-3 flex flex-col sm:flex-row items-center justify-between gap-2 text-xs text-stone-600">
            <span>© <?= date('Y') ?> <span class="text-orange-500 font-bold">La Tribu</span> · Hecho con <i class="fa-solid fa-heart text-red-500"></i> para restaurantes colombianos · Todos los derechos reservados</span>
            <div class="flex items-center gap-3">
                <i class="fa-brands fa-facebook hover:text-orange-400 cursor-pointer transition text-sm"></i>
                <i class="fa-brands fa-instagram hover:text-orange-400 cursor-pointer transition text-sm"></i>
                <i class="fa-brands fa-whatsapp hover:text-green-400 cursor-pointer transition text-sm"></i>
            </div>
        </div>

    </footer>

</div><!-- /col-right -->
</div><!-- /app -->
</body>
</html>
