<?php

function woo_order_form_callback() {
    ob_start();
    ?>


    <form method="POST" action="https://www.posterelite.com/api/Order_Creation.php">
        <input type="hidden" name="Auth_String" value="525HRD7867200143000">
        <input type="text" name="Account_Number" placeholder="Your Account Number">
        <input type="text" name="Date_Order_Received" placeholder="Date in MM-dd-YYYY">
        <input type="text" name="Client_Company" placeholder="Client company Name">
        <input type="text" name="Reference_Number" placeholder="Your Reference Number">
        <input type="text" name="Unique_ID" placeholder="Your Unique ID">
        <input type="text" name="PO_Number" placeholder="Your PO Number">
        <input type="text" name="Client_First_Name" placeholder="Client First Name">
        <input type="text" name="Client_Last_Name" placeholder="Client Last Name">
        <input type="text" name="Client_Street_Address_1" placeholder="Client Street Address 1">
        <input type="text" name="Client_Street_Address_2" placeholder="Client Street Address 2">
        <input type="text" name="Client_City" placeholder="City Name">
        <input type="text" name="Client_State" placeholder="State Code">
        <input type="text" name="Client_ZIP" placeholder="Zip Code">
        <input type="text" name="Client_Email_Address" placeholder="Client Email Address">
        <input type="text" name="Client_Phone_Number" placeholder="Client Phone Number">
        <select name="Order_Type">
            <option value="E-Update">E-Update</option>
            <option value="ePoster">ePoster</option>
        </select>
        <input type="text" name="Poster_State" placeholder="Poster State">
        <input type="text" name="Poster_Language" placeholder="Poster Language">
        <input type="submit" value="Add Record">
    </form>


    <?php return ob_get_clean();
}

add_shortcode( 'woo_order_table', 'woo_order_form_callback' );