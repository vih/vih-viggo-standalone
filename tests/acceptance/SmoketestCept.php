<?php
$I = new AcceptanceTester($scenario);
$I->wantTo('see a calendar');
$I->amOnPage('/');
$I->see('Home');
