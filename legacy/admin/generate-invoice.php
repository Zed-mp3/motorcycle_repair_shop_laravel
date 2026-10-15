<?php
require_once __DIR__ . '/../bootstrap.php';
require_once 'config/database.php';

$booking_id = $_GET['booking_id'] ?? '';

$conn = getConnection();

// Get booking details
$sql = "SELECT b.*, r.labor_cost 
        FROM bookings b 
        LEFT JOIN repairs r ON b.id = r.booking_id 
        WHERE b.booking_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $booking_id);
$stmt->execute();
$booking = $stmt->get_result()->fetch_assoc();

if (!$booking) {
    die("Booking not found");
}

// Get repair parts
$sql = "SELECT rp.*, p.part_name 
        FROM repair_parts rp 
        JOIN parts p ON rp.part_id = p.id 
        WHERE rp.repair_id = (SELECT id FROM repairs WHERE booking_id = ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $booking['id']);
$stmt->execute();
$parts = $stmt->get_result();

$parts_total = 0;
$parts_list = [];
while($part = $parts->fetch_assoc()) {
    $total = $part['quantity'] * $part['price_at_time'];
    $parts_total += $total;
    $parts_list[] = $part;
}

$labor_cost = $booking['labor_cost'] ?? 0;
$grand_total = $parts_total + $labor_cost;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice - <?php echo $booking['booking_id']; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="/assets/css/portal.css">
</head>
<body>
    <div class="container mt-4 mb-4">
        <div class="row">
            <div class="col-md-12 text-center no-print mb-3">
                <button onclick="window.print()" class="btn btn-primary">
                    <i class="fas fa-print"></i> Print Invoice
                </button>
                <button onclick="window.close()" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Close
                </button>
            </div>
        </div>
        
        <div class="invoice-container">
            <div class="invoice-header">
                <div class="row">
                    <div class="col-md-6">
                        <h1 class="invoice-title"><i class="fas fa-motorcycle"></i> MotoRepair Pro</h1>
                        <p>
                            123 Repair Street<br>
                            Motor City, MC 12345<br>
                            Phone: (555) 123-4567<br>
                            Email: info@motorepair.com
                        </p>
                    </div>
                    <div class="col-md-6 text-end">
                        <h3>INVOICE</h3>
                        <p>
                            <strong>Invoice #:</strong> <?php echo $booking['booking_id']; ?><br>
                            <strong>Date:</strong> <?php echo date('F d, Y'); ?><br>
                            <strong>Queue #:</strong> <?php echo $booking['queue_no']; ?>
                        </p>
                    </div>
                </div>
            </div>

            <div class="row mb-4">
                <div class="col-md-6">
                    <h5>Bill To:</h5>
                    <p>
                        <strong><?php echo $booking['full_name']; ?></strong><br>
                        <?php echo $booking['contact_number']; ?>
                    </p>
                </div>
                <div class="col-md-6">
                    <h5>Service Details:</h5>
                    <p>
                        <strong>Motorcycle:</strong> <?php echo $booking['motorcycle_model']; ?><br>
                        <strong>Service Type:</strong> <?php echo $booking['service_type']; ?><br>
                        <strong>Issue:</strong> <?php echo $booking['problem_description']; ?>
                    </p>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead class="table-dark">
                        <tr>
                            <th>Description</th>
                            <th class="text-center">Quantity</th>
                            <th class="text-end">Unit Price</th>
                            <th class="text-end">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(count($parts_list) > 0): ?>
                            <?php foreach($parts_list as $part): ?>
                                <tr>
                                    <td><?php echo $part['part_name']; ?></td>
                                    <td class="text-center"><?php echo $part['quantity']; ?></td>
                                    <td class="text-end">$<?php echo number_format($part['price_at_time'], 2); ?></td>
                                    <td class="text-end">$<?php echo number_format($part['quantity'] * $part['price_at_time'], 2); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        
                        <?php if($labor_cost > 0): ?>
                            <tr>
                                <td colspan="3"><strong>Labor Charge</strong></td>
                                <td class="text-end">$<?php echo number_format($labor_cost, 2); ?></td>
                            </tr>
                        <?php endif; ?>
                        
                        <?php if($parts_total == 0 && $labor_cost == 0): ?>
                            <tr>
                                <td colspan="4" class="text-center">No charges added yet</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                    <tfoot>
                        <tr class="table-info">
                            <th colspan="3" class="text-end">Subtotal:</th>
                            <th class="text-end">$<?php echo number_format($parts_total + $labor_cost, 2); ?></th>
                        </tr>
                        <tr class="table-success">
                            <th colspan="3" class="text-end">Total Amount Due:</th>
                            <th class="text-end">$<?php echo number_format($grand_total, 2); ?></th>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <div class="total-box">
                <div class="row">
                    <div class="col-md-6">
                        <p><strong>Payment Terms:</strong> Due upon receipt</p>
                        <p><strong>Payment Methods:</strong> Cash, Credit Card, Bank Transfer</p>
                    </div>
                    <div class="col-md-6 text-end">
                        <p><strong>Thank you for choosing MotoRepair Pro!</strong></p>
                        <p>For questions, please contact our support team.</p>
                    </div>
                </div>
            </div>
            
            <div class="text-center mt-4">
                <small>This is a computer-generated invoice. No signature required.</small>
            </div>
        </div>
    </div>
</body>
</html>
