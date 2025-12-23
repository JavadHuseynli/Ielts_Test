                </div>
            </main>

            <!-- Modern Footer -->
            <footer class="glass border-t border-white/20 py-6">
                <div class="px-4 sm:px-6 lg:px-8">
                    <div class="flex flex-col md:flex-row items-center justify-between">
                        <p class="text-sm text-gray-600 font-medium">
                            © <?php echo date('Y'); ?> Təhsil Sistemi. Bütün hüquqlar qorunur.
                        </p>
                        <div class="flex items-center space-x-4 mt-4 md:mt-0">
                            <span class="px-3 py-1 text-xs font-semibold text-primary-700 bg-primary-100 rounded-full">
                                v2.0
                            </span>
                            <div class="flex items-center space-x-1">
                                <div class="w-2 h-2 bg-green-500 rounded-full animate-pulse"></div>
                                <span class="text-xs text-gray-600">Sistem aktiv</span>
                            </div>
                        </div>
                    </div>
                </div>
            </footer>
        </div>
    </div>

    <!-- Mobile Menu Overlay -->
    <div x-show="mobileMenuOpen"
         @click="mobileMenuOpen = false"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 bg-black/50 backdrop-blur-sm z-40 lg:hidden"></div>
</body>
</html>
