<?php
session_start();
include "db.php";

$message = "";
$error = "";

// CHECK ADMIN LOGIN
if(!isset($_SESSION['admin'])){
    header("Location: admin_login.php");
    exit();
}

if(isset($_POST['add']))
{
    $admin_id = $_SESSION['admin'];
    $title = trim($_POST['title']);

    // MULTIPLE CATEGORY (branches)
    if(isset($_POST['category']) && is_array($_POST['category'])){
        $category_array = array_map('strtoupper', $_POST['category']);
        $category = implode(",", $category_array);
    } else {
        $category = "";
    }

    $description = trim($_POST['description']);
    $reg_start = $_POST['reg_start'];
    $reg_end = $_POST['reg_end'];
    $event_date = $_POST['event_date'];
    $venue = trim($_POST['venue']);
    $contact_phone = trim($_POST['contact_phone']);
    $event_head = trim($_POST['event_head']);

    $is_paid = $_POST['is_paid'];
    $price_single = 0;
    $price_double = 0;
    $qr_name = "";

    if($is_paid == 1){
        $paid_option = $_POST['paid_option'];

        if($paid_option == 'single'){
            $price_single = floatval($_POST['price_single']);
        } 
        elseif($paid_option == 'double'){
            $price_double = floatval($_POST['price_double']);
        } 
        elseif($paid_option == 'both'){
            $price_single = floatval($_POST['price_single']);
            $price_double = floatval($_POST['price_double']);
        }

        // QR UPLOAD
        if(isset($_FILES['qr_code']) && $_FILES['qr_code']['name'] != ""){
            $target_dir = "uploads/";
            if(!is_dir($target_dir)){
                mkdir($target_dir, 0777, true);
            }
            $qr_name = time() . "_" . basename($_FILES["qr_code"]["name"]);
            $target_file = $target_dir . $qr_name;
            if(move_uploaded_file($_FILES["qr_code"]["tmp_name"], $target_file)){
                // success
            } else {
                $error = "❌ Failed to upload QR code!";
            }
        } else {
            $error = "❌ Please upload QR code for paid event!";
        }
    }

    if(empty($error)){
        $stmt = $conn->prepare("INSERT INTO events 
        (title, category, description, reg_start, reg_end, event_date, venue, contact_phone, event_head, is_paid, price_single, price_double, qr_code, admin_id) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

        $stmt->bind_param("sssssssssdddsi",
            $title,
            $category,
            $description,
            $reg_start,
            $reg_end,
            $event_date,
            $venue,
            $contact_phone,
            $event_head,
            $is_paid,
            $price_single,
            $price_double,
            $qr_name,
            $admin_id
        );

        if($stmt->execute()){
            $message = "🎉 Event Added Successfully!";
        } else {
            $error = "❌ Database error: " . $stmt->error;
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>Add Event - College Event Hub</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        /* Base styles */
        input, select, textarea, button {
            font-size: 16px;
        }
        .card-hover {
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        .card-hover:hover {
            transform: translateY(-2px);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.02);
        }
        .checkbox-group label {
            display: inline-flex;
            align-items: center;
            margin-right: 1rem;
            margin-bottom: 0.5rem;
        }
        @media (max-width: 640px) {
            .checkbox-group {
                display: flex;
                flex-wrap: wrap;
                gap: 0.5rem;
            }
        }
        input:focus, select:focus, textarea:focus {
            outline: none;
            ring: 2px solid #3b82f6;
        }
        input[type="file"]::file-selector-button {
            background-color: #eff6ff;
            color: #1e40af;
            border-radius: 9999px;
            border: none;
            padding: 0.5rem 1rem;
            font-weight: 500;
            transition: all 0.2s;
        }
        input[type="file"]::file-selector-button:hover {
            background-color: #dbeafe;
            cursor: pointer;
        }
    </style>
</head>
<body class="bg-gradient-to-br from-slate-100 to-gray-200 min-h-screen">

<nav class="bg-white shadow-lg sticky top-0 z-50 border-b border-gray-100">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center h-16">
            <div class="flex items-center space-x-2">
                <i class="fas fa-calendar-alt text-blue-700 text-2xl"></i>
                <span class="font-bold text-xl text-gray-800 tracking-tight">College Event Hub</span>
            </div>
            <div class="flex items-center space-x-5">
                <a href="admin_dashboard.php" class="text-gray-600 hover:text-blue-700 transition duration-200 font-medium">
                    <i class="fas fa-tachometer-alt"></i> <span class="hidden sm:inline">Dashboard</span>
                </a>
                <a href="logout.php" class="text-red-500 hover:text-red-700 transition duration-200 font-medium">
                    <i class="fas fa-sign-out-alt"></i> <span class="hidden sm:inline">Logout</span>
                </a>
            </div>
        </div>
    </div>
</nav>

<div class="max-w-4xl mx-auto px-4 py-8 sm:px-6 lg:px-8">
    <div class="bg-white rounded-2xl shadow-2xl overflow-hidden card-hover border border-gray-100">
        <div class="bg-gradient-to-r from-blue-800 to-indigo-900 px-6 py-5">
            <h1 class="text-2xl font-bold text-white flex items-center">
                <i class="fas fa-plus-circle mr-3 text-blue-200"></i> Create New Event
            </h1>
            <p class="text-blue-100 text-sm mt-1 opacity-90">Fill in the details below to add an event</p>
        </div>

        <div class="p-6 sm:p-8">
            <?php if($message != ""): ?>
                <div class="mb-6 p-4 bg-emerald-50 border-l-4 border-emerald-500 text-emerald-700 rounded-lg flex items-start shadow-sm">
                    <i class="fas fa-check-circle mt-0.5 mr-3 text-emerald-500"></i>
                    <span><?php echo htmlspecialchars($message); ?></span>
                </div>
            <?php endif; ?>
            <?php if($error != ""): ?>
                <div class="mb-6 p-4 bg-rose-50 border-l-4 border-rose-500 text-rose-700 rounded-lg flex items-start shadow-sm">
                    <i class="fas fa-exclamation-triangle mt-0.5 mr-3 text-rose-500"></i>
                    <span><?php echo htmlspecialchars($error); ?></span>
                </div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data" class="space-y-6">
                <div>
                    <label class="block text-gray-700 font-semibold mb-2">Event Title <span class="text-red-500">*</span></label>
                    <input type="text" name="title" placeholder="e.g., Tech Fest 2025" required
                           class="w-full px-4 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-200 bg-gray-50 focus:bg-white">
                </div>

                <div>
                    <label class="block text-gray-700 font-semibold mb-2">Eligible Branches <span class="text-red-500">*</span></label>
                    <div class="checkbox-group bg-gray-50 p-4 rounded-xl border border-gray-200">
                        <?php 
                        $branches = ['CE', 'IT', 'AIDS', 'AIML', 'MECH', 'ELEC', 'COMMON'];
                        foreach($branches as $branch): ?>
                        <label class="inline-flex items-center mr-5 mb-2 cursor-pointer">
                            <input type="checkbox" name="category[]" value="<?php echo $branch; ?>" class="form-checkbox h-5 w-5 text-blue-600 rounded border-gray-300 focus:ring-blue-500">
                            <span class="ml-2 text-gray-700"><?php echo $branch; ?></span>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div>
                    <label class="block text-gray-700 font-semibold mb-2">Description <span class="text-red-500">*</span></label>
                    <textarea name="description" rows="4" placeholder="Detailed description of the event..." required
                              class="w-full px-4 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-200 bg-gray-50 focus:bg-white"></textarea>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
                    <div>
                        <label class="block text-gray-700 font-semibold mb-2">Reg Start <span class="text-red-500">*</span></label>
                        <input type="date" name="reg_start" required class="w-full px-4 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 bg-gray-50">
                    </div>
                    <div>
                        <label class="block text-gray-700 font-semibold mb-2">Reg End <span class="text-red-500">*</span></label>
                        <input type="date" name="reg_end" required class="w-full px-4 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 bg-gray-50">
                    </div>
                    <div>
                        <label class="block text-gray-700 font-semibold mb-2">Event Date <span class="text-red-500">*</span></label>
                        <input type="date" name="event_date" required class="w-full px-4 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 bg-gray-50">
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
                    <div>
                        <label class="block text-gray-700 font-semibold mb-2">Venue</label>
                        <input type="text" name="venue" placeholder="Auditorium, Lab, etc." required class="w-full px-4 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 bg-gray-50">
                    </div>
                    <div>
                        <label class="block text-gray-700 font-semibold mb-2">Contact Phone</label>
                        <input type="text" name="contact_phone" placeholder="+91 98765 43210" required class="w-full px-4 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 bg-gray-50">
                    </div>
                    <div>
                        <label class="block text-gray-700 font-semibold mb-2">Event Head</label>
                        <input type="text" name="event_head" placeholder="Professor / Coordinator" required class="w-full px-4 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 bg-gray-50">
                    </div>
                </div>

                <div>
                    <label class="block text-gray-700 font-semibold mb-2">Event Type</label>
                    <select name="is_paid" id="is_paid" class="w-full px-4 py-2.5 border border-gray-300 rounded-xl bg-white focus:ring-2 focus:ring-blue-500">
                        <option value="0">Free Event</option>
                        <option value="1">Paid Event</option>
                    </select>
                </div>

                <div id="paid_section" class="hidden space-y-5 bg-gray-50 p-5 rounded-xl border border-gray-200">
                    <div>
                        <label class="block text-gray-700 font-semibold mb-2">Pricing Option</label>
                        <select name="paid_option" id="paid_option" class="w-full px-4 py-2.5 border border-gray-300 rounded-xl bg-white focus:ring-2 focus:ring-blue-500">
                            <option value="single">Single Registration</option>
                            <option value="double">Double / Group Registration</option>
                            <option value="both">Both Single & Double</option>
                        </select>
                    </div>

                    <div id="single_price_block">
                        <label class="block text-gray-700 font-medium mb-1">Single Registration Price (₹)</label>
                        <input type="number" name="price_single" id="price_single" step="0.01" placeholder="e.g., 200" class="w-full px-4 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 bg-white">
                    </div>

                    <div id="double_price_block">
                        <label class="block text-gray-700 font-medium mb-1">Double / Group Price (₹)</label>
                        <input type="number" name="price_double" id="price_double" step="0.01" placeholder="e.g., 350" class="w-full px-4 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 bg-white">
                    </div>

                    <div>
                        <label class="block text-gray-700 font-semibold mb-2">Payment QR Code <span class="text-red-500">*</span></label>
                        <input type="file" name="qr_code" accept="image/*" class="w-full text-gray-600 file:mr-4 file:py-2 file:px-5 file:rounded-full file:border-0 file:text-sm file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 transition">
                        <p class="text-xs text-gray-500 mt-2">Upload a QR code image (jpg, png) for students to scan and pay.</p>
                    </div>
                </div>

                <div class="pt-4">
                    <button type="submit" name="add" class="w-full bg-gradient-to-r from-blue-700 to-indigo-800 text-white font-semibold py-3.5 px-6 rounded-xl shadow-md hover:shadow-xl transform hover:scale-[1.02] transition duration-200 flex items-center justify-center">
                        <i class="fas fa-save mr-2"></i> Add Event
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    // Paid/Free toggle logic
    const isPaidSelect = document.getElementById('is_paid');
    const paidSection = document.getElementById('paid_section');
    const paidOptionSelect = document.getElementById('paid_option');
    const singleBlock = document.getElementById('single_price_block');
    const doubleBlock = document.getElementById('double_price_block');
    const singleInput = document.getElementById('price_single');
    const doubleInput = document.getElementById('price_double');

    function togglePaidSection() {
        paidSection.style.display = isPaidSelect.value == '1' ? 'block' : 'none';
        if (isPaidSelect.value != '1') {
            singleInput.removeAttribute('required');
            doubleInput.removeAttribute('required');
        } else {
            updatePriceFields();
        }
    }

    function updatePriceFields() {
        const option = paidOptionSelect.value;
        singleBlock.style.display = 'block';
        doubleBlock.style.display = 'block';
        singleInput.removeAttribute('required');
        doubleInput.removeAttribute('required');

        if (option === 'single') {
            doubleBlock.style.display = 'none';
            doubleInput.value = '';
            singleInput.setAttribute('required', 'required');
        } else if (option === 'double') {
            singleBlock.style.display = 'none';
            singleInput.value = '';
            doubleInput.setAttribute('required', 'required');
        } else if (option === 'both') {
            singleInput.setAttribute('required', 'required');
            doubleInput.setAttribute('required', 'required');
        }
    }

    isPaidSelect.addEventListener('change', togglePaidSection);
    paidOptionSelect.addEventListener('change', updatePriceFields);
    togglePaidSection();
    updatePriceFields();
</script>

</body>
</html>