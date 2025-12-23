                </div>
            </main>

            <!-- Footer -->
            <footer class="bg-white border-t border-gray-200 py-4">
                <div class="px-4 sm:px-6 lg:px-8">
                    <p class="text-center text-sm text-gray-500">
                        © <?php echo date('Y'); ?> Təhsil Sistemi. Bütün hüquqlar qorunur.
                    </p>
                </div>
            </footer>
        </div>
    </div>

    <!-- Mobile Menu Overlay -->
    <div x-show="mobileMenuOpen" @click="mobileMenuOpen = false" class="fixed inset-0 bg-black bg-opacity-50 z-40 lg:hidden" x-transition></div>
</body>
</html>
