<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pixishoot Calendar</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/fullcalendar/3.10.0/fullcalendar.min.css">
    <link rel="stylesheet" href="assets/css/styles.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/jquery.dataTables.min.css">
</head>
<body>
    <div class="container">
        <header class="calendar-header">
            <!-- Controls Wrapper for Dropdown and Label -->
         
            <div class="controls">
                <label for="serviceFilter">Filter by Service:</label>
                <select id="serviceFilter" class="styled-dropdown">
                    <option value="">All Services</option>
                    <option value="On Model">On Model</option>
                    <option value="Ghost Mannequin">Ghost Mannequin</option>
                    <option value="Product Photography">Product Photography</option>
                </select>

                <!-- View Records Button -->
                <button id="viewRecordsBtn" class="view-records-btn">View Records</button>
            </div>

            <!-- Color Legends -->
            <div class="legend">
                <div class="legend-item">
                    <span class="color-box" style="background-color: #34495e;"></span>
                    <span>On Model</span>
                </div>
                <div class="legend-item">
                    <span class="color-box" style="background-color: #f39c12;"></span>
                    <span>Ghost Mannequin</span>
                </div>
                <div class="legend-item">
                    <span class="color-box" style="background-color: #1abc9c;"></span>
                    <span>Product Photography</span>
                </div>
            </div>
        </header>

        <!-- FullCalendar -->
        <div id="calendar"></div>

        <!-- Modal for Event Creation and Editing -->
        <div id="eventModal">
            <div class="modal-header">
                <h3 id="modalHeader">Create New Event</h3>
                <button class="close-btn" onclick="closeModal()">×</button>
            </div>
            <div class="modal-content">
                <label for="brandName">Brand Name:</label>
                <input type="text" id="brandName" placeholder="Enter brand name" required>

                <label for="serviceSelect">Service:</label>
                <select id="serviceSelect" class="styled-dropdown">
                    <option value="On Model">On Model</option>
                    <option value="Ghost Mannequin">Ghost Mannequin</option>
                    <option value="Product Photography">Product Photography</option>
                </select>

                <label for="eventDuration">Event Duration (hours):</label>
                <input type="number" id="eventDuration" placeholder="Duration in hours" min="1" required>

                <label for="startTime">Start Time:</label>
                <select id="startTime" name="startTime"> 
                    <option value="" disabled selected>Select a start time</option>
                </select>

                <div class="button-group">
                    <button class="book-btn" id="submitEventBtn">Create Event</button>
                   
                    </div>

                <!-- Buttons for Edit Mode -->
                <div class="button-group" id="editButtonGroup" style="display:flex;">
                    <button class="book-btn" id="updateEventBtn">Update</button>
                    <button class="delete-btn" id="deleteEventBtn">Delete</button>
               <!--  <button class="cancel-btn" id="cancelEventBtn" onclick="closeModal()">Cancel</button> -->
                
                    </div>

                <div class="loading-spinner" id="loadingSpinner"></div>
            </div>
        </div>

       <div id="overlay" class="overlay"></div>
<!-- Modal for Event Records -->
<div id="recordsModal" class="modal">
    <div class="modal-content">
        <button class="close-btn" id="closeModalBtn">X</button>
        <div>
            <label for="serviceModalFilter">Select Service :</label> &nbsp;
            <select id="serviceModalFilter" class="styled-dropdown">
                <option value="">All Services</option>
                <option value="On Model">On Model</option>
                <option value="Ghost Mannequin">Ghost Mannequin</option>
                <option value="Product Photography">Product Photography</option>
            </select>
        </div>
        <div>
            <label for="dateModalFilter"> Choose Date: </label> &nbsp;&nbsp;&nbsp;&nbsp;
            <input type="date" id="dateModalFilter" value="">
        </div>
        <table id="recordsTable" class="display">
            <thead>
                <tr>
            <th>Brand Name</th>
            <th>Service</th>
            <th>Date</th>
            <th>Start Time</th>
            <th>End Time</th>
            <th>Actions</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
        <!-- Export to PDF Button -->
        <button id="exportToPdfBtn">Export to PDF</button>
        <div class="loading-spinner" id="loadingSpinner" style="display:none;">Loading...</div>
    </div>
</div>


        <!-- Load Scripts -->
        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script> <!-- jQuery should be loaded first -->
        <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.1/moment.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/fullcalendar/3.10.0/fullcalendar.min.js"></script>
        <script src="assets/js/scripts.js"></script>
  
        <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script> <!-- DataTables should be loaded after jQuery -->
    
        <!-- Include jsPDF -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>

<!-- Include jsPDF autoTable plugin -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.18/jspdf.plugin.autotable.min.js"></script>
<!-- Bootstrap 5 JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.0/dist/js/bootstrap.bundle.min.js"></script>

        </div>
</body>
</html>
