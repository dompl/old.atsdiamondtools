<div class="corona">
    <div id="corona-note">
        <?php /*
            <p>We will be implementing a 10% increase on all products from the 1<sup>st</sup> March 2021.<br />This is the first time in 7 years we have increased our prices and is due to unprecedented market conditions and increased shipping costs with our suppliers in both Europe and Asia.</p>
         */?>
        <?php
    $html   = '<p>Royal Mail and Parcelforce industrial action will be taking place on the following dates: Thursday 13/10/22, Thursday 20/10/22, Tuesday 25/10/22. Collections and deliveries will be effected on these specific days.</p>';
    $image  = wpimage( 'img=38181&h=120&crop=false&retina=false&upscale=false' );
    $paypal = '<div class=paypal><div class=image><img src="' . $image . '" alt="PayPal Credit"></div><p>Paypal are now able to offer buy now pay later interest free on all purchases through the website</p></div>';

    echo $paypal;
?>

    </div>
</div>