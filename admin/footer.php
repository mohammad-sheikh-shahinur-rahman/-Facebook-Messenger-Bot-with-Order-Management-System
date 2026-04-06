            </div>
        </div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    
    <!-- jQuery -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.0/jquery.min.js"></script>
    
    <!-- Data Tables JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/datatables/1.13.6/datatables.min.js"></script>
    
    <script>
        // Initialize DataTables
        $(document).ready(function() {
            if ($('table').length) {
                $('table').DataTable({
                    "pageLength": 25,
                    "order": [[0, "desc"]],
                    "responsive": true,
                    "language": {
                        "search": "Search:",
                        "lengthMenu": "Show _MENU_ entries",
                        "info": "Showing _START_ to _END_ of _TOTAL_ entries"
                    }
                });
            }
        });
        
        // Confirm before delete
        function confirmDelete() {
            return confirm('Are you sure you want to delete this order? This action cannot be undone.');
        }
        
        // Export to CSV
        function exportToCSV(filename) {
            var csv = [];
            var rows = document.querySelectorAll("table tr");
            
            for (var i = 0; i < rows.length; i++) {
                var row = [], cols = rows[i].querySelectorAll("td, th");
                
                for (var j = 0; j < cols.length; j++) {
                    row.push(cols[j].innerText);
                }
                
                csv.push(row.join(","));
            }
            
            downloadCSV(csv.join("\n"), filename);
        }
        
        function downloadCSV(csv, filename) {
            var csvFile;
            var downloadLink;
            
            csvFile = new Blob([csv], {type: "text/csv"});
            downloadLink = document.createElement("a");
            downloadLink.href = URL.createObjectURL(csvFile);
            downloadLink.download = filename;
            document.body.appendChild(downloadLink);
            downloadLink.click();
        }
    </script>
</body>
</html>
