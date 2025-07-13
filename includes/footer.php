        </div>
    </div>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <h5><i class="fas fa-users me-2"></i>Sistema de Cadastro de Fiscais</h5>
                    <p class="mb-0">Sistema moderno para gerenciamento de fiscais de prova</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <p class="mb-0">
                        <i class="fas fa-calendar me-1"></i>
                        <?= date('Y') ?> - Todos os direitos reservados
                    </p>
                </div>
            </div>
        </div>
    </footer>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <script>
        // Função para mostrar loading
        function showLoading() {
            const loadingElement = document.getElementById('loading');
            if (loadingElement) {
                loadingElement.classList.remove('d-none');
                loadingElement.style.display = 'flex';
            }
        }

        // Função para esconder loading
        function hideLoading() {
            const loadingElement = document.getElementById('loading');
            if (loadingElement) {
                loadingElement.classList.add('d-none');
                loadingElement.style.display = 'none';
            }
        }

        // Função para formatar CPF
        function formatCPF(cpf) {
            cpf = cpf.replace(/\D/g, '');
            return cpf.replace(/(\d{3})(\d{3})(\d{3})(\d{2})/, '$1.$2.$3-$4');
        }

        // Função para formatar telefone
        function formatPhone(phone) {
            phone = phone.replace(/\D/g, '');
            if (phone.length === 11) {
                return phone.replace(/(\d{2})(\d{5})(\d{4})/, '($1) $2-$3');
            }
            return phone;
        }

        // Função para validar CPF
        function validateCPF(cpf) {
            cpf = cpf.replace(/\D/g, '');
            
            if (cpf.length !== 11 || /^(\d)\1+$/.test(cpf)) {
                return false;
            }
            
            let sum = 0;
            for (let i = 0; i < 9; i++) {
                sum += parseInt(cpf.charAt(i)) * (10 - i);
            }
            let remainder = (sum * 10) % 11;
            if (remainder === 10 || remainder === 11) remainder = 0;
            if (remainder !== parseInt(cpf.charAt(9))) return false;
            
            sum = 0;
            for (let i = 0; i < 10; i++) {
                sum += parseInt(cpf.charAt(i)) * (11 - i);
            }
            remainder = (sum * 10) % 11;
            if (remainder === 10 || remainder === 11) remainder = 0;
            if (remainder !== parseInt(cpf.charAt(10))) return false;
            
            return true;
        }

        // Função para validar email
        function validateEmail(email) {
            const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return re.test(email);
        }

        // Função para confirmar ações
        function confirmAction(message, callback) {
            Swal.fire({
                title: 'Confirmar',
                text: message,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Sim',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed && callback) {
                    callback();
                }
            });
        }

        // Função para mostrar mensagens
        function showMessage(message, type = 'success') {
            Swal.fire({
                title: type === 'success' ? 'Sucesso!' : 'Atenção!',
                text: message,
                icon: type,
                timer: type === 'success' ? 3000 : undefined,
                timerProgressBar: type === 'success'
            });
        }

        // Inicializar DataTables
        $(document).ready(function() {
            if ($('.datatable').length) {
                $('.datatable').DataTable({
                    language: {
                        url: 'https://cdn.datatables.net/plug-ins/1.13.6/i18n/pt-BR.json'
                    },
                    responsive: true,
                    pageLength: 25,
                    order: [[0, 'desc']]
                });
            }

            // Auto-hide alerts after 5 seconds
            setTimeout(function() {
                $('.alert').fadeOut();
            }, 5000);

            // Form validation - removido showLoading automático para evitar conflitos
        });

        // Função para exportar dados
        function exportData(format) {
            showLoading();
            
            <?php
            // Detectar se estamos na pasta admin
            $isAdmin = strpos($_SERVER['REQUEST_URI'], '/admin/') !== false;
            $exportPath = $isAdmin ? 'export.php' : 'admin/export.php';
            ?>
            
            $.ajax({
                url: '<?= $exportPath ?>',
                method: 'POST',
                data: { format: format },
                success: function(response) {
                    hideLoading();
                    if (response.success) {
                        // Criar link para download
                        const link = document.createElement('a');
                        link.href = 'data:text/csv;charset=utf-8,' + encodeURIComponent(response.data);
                        link.download = 'fiscais_' + new Date().toISOString().split('T')[0] + '.' + format;
                        link.click();
                    } else {
                        showMessage(response.message, 'error');
                    }
                },
                error: function() {
                    hideLoading();
                    showMessage('Erro ao exportar dados', 'error');
                }
            });
        }

        // Função para deletar fiscal
        function deleteFiscal(id) {
            confirmAction('Tem certeza que deseja excluir este fiscal?', function() {
                showLoading();
                
                <?php
                $deletePath = $isAdmin ? 'delete_fiscal.php' : 'admin/delete_fiscal.php';
                ?>
                
                $.ajax({
                    url: '<?= $deletePath ?>',
                    method: 'POST',
                    data: { id: id },
                    success: function(response) {
                        hideLoading();
                        if (response.success) {
                            showMessage('Fiscal excluído com sucesso!');
                            setTimeout(function() {
                                location.reload();
                            }, 1500);
                        } else {
                            showMessage(response.message, 'error');
                        }
                    },
                    error: function() {
                        hideLoading();
                        showMessage('Erro ao excluir fiscal', 'error');
                    }
                });
            });
        }

        // Função para alterar status
        function changeStatus(id, status) {
            showLoading();
            
            <?php
            $statusPath = $isAdmin ? 'change_status.php' : 'admin/change_status.php';
            ?>
            
            $.ajax({
                url: '<?= $statusPath ?>',
                method: 'POST',
                data: { id: id, status: status },
                success: function(response) {
                    hideLoading();
                    if (response.success) {
                        showMessage('Status alterado com sucesso!');
                        setTimeout(function() {
                            location.reload();
                        }, 1500);
                    } else {
                        showMessage(response.message, 'error');
                    }
                },
                error: function() {
                    hideLoading();
                    showMessage('Erro ao alterar status', 'error');
                }
            });
        }
    </script>
</body>
</html> 