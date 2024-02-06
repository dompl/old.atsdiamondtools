<?php

// Randomly select one offer

// Function to return a random offer
function getRandomOffer() {

    $offers = [
        ["title" => "Unlock Exclusive Savings with ATS!", "description" => "Get access to special pricing and exclusive deals only available to our newsletter subscribers. Save more on your next purchase with ATS!"],
        ["title" => "Insider Deals: Join the ATS Club!", "description" => "Become part of an exclusive group that enjoys insider deals and promotions. The ATS Club is where savings meet quality!"],
        ["title" => "ATS Exclusive: Offers You Can't Miss!", "description" => "Dive into a world of exclusive offers tailored just for you. Don't let these unbeatable ATS deals pass you by!"],
        ["title" => "Subscribe for Special ATS Insider Discounts!", "description" => "Your subscription unlocks a realm of special discounts. Be an ATS insider and enjoy lower prices on premium tools!"],
        ["title" => "Be the First: ATS Deals & Steals!", "description" => "Stay ahead of the game with early access to ATS deals and steals. Subscribe and never miss out on a bargain!"],
        ["title" => "Your VIP Pass to ATS Savings!", "description" => "Your subscription acts as a VIP pass to the best savings ATS has to offer. Exclusive offers now within your reach!"],
        ["title" => "Get Ahead with ATS Exclusive Offers!", "description" => "Lead the pack with early access to exclusive ATS offers. Premium tools, premium savings, just for you!"],
        ["title" => "Join Our Circle: Exclusive ATS Perks Inside!", "description" => "Enter our circle and unlock exclusive ATS perks that enhance your shopping experience. Special offers, just a subscription away!"],
        ["title" => "ATS Insiders: Unlock Your Exclusive Discounts!", "description" => "Join the ATS insiders for unparalleled access to exclusive discounts. Your next ATS purchase could be at a steal!"],
        ["title" => "Save Big: Subscribe for ATS Exclusive Offers!", "description" => "Big savings are on the horizon with ATS exclusive offers. Subscribe today and watch your savings grow!"]
    ];
    return $offers[array_rand( $offers )];
}

?>