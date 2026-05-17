<?php
header("Content-Type: application/json");
require "db_connection.php";

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $items = json_decode($_POST["items"] ?? "[]", true);
    $addons = json_decode($_POST["addons"] ?? "[]", true);

    if (!is_array($items) || count($items) === 0) {
        echo json_encode(["success" => false, "message" => "No checkout items received."]);
        exit;
    }

    $conn->begin_transaction();

    foreach ($items as $item) {
        $isCustom = !empty($item["is_custom"]) || (($item["productId"] ?? "") === "custom");

        if ($isCustom) {
            $customData = $item["custom_data"] ?? $item;

            $productCounts = [];

            foreach (($customData["flowerCounts"] ?? []) as $productId => $qty) {
                $productCounts[(int)$productId] = ($productCounts[(int)$productId] ?? 0) + (int)$qty;
            }

            foreach (($customData["fillerCounts"] ?? []) as $productId => $qty) {
                $productCounts[(int)$productId] = ($productCounts[(int)$productId] ?? 0) + (int)$qty;
            }

            foreach (($customData["selectedAddons"] ?? []) as $addon) {
                $productId = (int)($addon["id"] ?? 0);
                $qty = (int)($addon["qty"] ?? 1);

                if ($productId > 0) {
                    $productCounts[$productId] = ($productCounts[$productId] ?? 0) + $qty;
                }
            }

            foreach ($productCounts as $productId => $qty) {
                if ($productId <= 0 || $qty <= 0) continue;

                $stmt = $conn->prepare("SELECT stock, product_name FROM product WHERE product_id = ? FOR UPDATE");
                $stmt->bind_param("i", $productId);
                $stmt->execute();

                $result = $stmt->get_result();
                $product = $result->fetch_assoc();

                if (!$product) {
                    throw new Exception("Product not found: " . $productId);
                }

                if ((int)$product["stock"] < $qty) {
                    throw new Exception("Not enough stock for " . $product["product_name"]);
                }

                $update = $conn->prepare("UPDATE product SET stock = stock - ? WHERE product_id = ?");
                $update->bind_param("ii", $qty, $productId);
                $update->execute();
            }
        } else {
            $bouquetId = (int)($item["productId"] ?? $item["bouquet_id"] ?? 0);
            $qty = (int)($item["qty"] ?? 0);

            if ($bouquetId <= 0 || $qty <= 0) {
                throw new Exception("Invalid bouquet item in checkout.");
            }

            $stmt = $conn->prepare("SELECT stock FROM bouquet WHERE bouquet_id = ? FOR UPDATE");
            $stmt->bind_param("i", $bouquetId);
            $stmt->execute();

            $result = $stmt->get_result();
            $bouquet = $result->fetch_assoc();

            if (!$bouquet) {
                throw new Exception("Bouquet not found.");
            }

            if ((int)$bouquet["stock"] < $qty) {
                throw new Exception("Not enough stock for one or more bouquets.");
            }

            $update = $conn->prepare("UPDATE bouquet SET stock = stock - ? WHERE bouquet_id = ?");
            $update->bind_param("ii", $qty, $bouquetId);
            $update->execute();
        }
    }

    foreach ($addons as $addon) {
        $productId = (int)($addon["id"] ?? $addon["addonId"] ?? 0);
        $qty = (int)($addon["qty"] ?? 1);

        if ($productId <= 0 || $qty <= 0) continue;

        $stmt = $conn->prepare("SELECT stock, product_name FROM product WHERE product_id = ? FOR UPDATE");
        $stmt->bind_param("i", $productId);
        $stmt->execute();

        $result = $stmt->get_result();
        $product = $result->fetch_assoc();

        if (!$product) {
            throw new Exception("Add-on product not found: " . $productId);
        }

        if ((int)$product["stock"] < $qty) {
            throw new Exception("Not enough stock for " . $product["product_name"]);
        }

        $update = $conn->prepare("UPDATE product SET stock = stock - ? WHERE product_id = ?");
        $update->bind_param("ii", $qty, $productId);
        $update->execute();
    }

    $conn->commit();

    echo json_encode([
        "success" => true,
        "message" => "Stock updated."
    ]);
} catch (Throwable $e) {
    if (isset($conn)) {
        $conn->rollback();
    }

    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => $e->getMessage()
    ]);
}
?>