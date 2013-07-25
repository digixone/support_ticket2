<?php
/*******************************************************************************
*  Title: Help Desk Software HESK
*  Version: 2.3 from 15th September 2011
*  Author: Klemen Stirn
*  Website: http://www.hesk.com
********************************************************************************
*  COPYRIGHT AND TRADEMARK NOTICE
*  Copyright 2005-2011 Klemen Stirn. All Rights Reserved.
*  HESK is a registered trademark of Klemen Stirn.

*  The HESK may be used and modified free of charge by anyone
*  AS LONG AS COPYRIGHT NOTICES AND ALL THE COMMENTS REMAIN INTACT.
*  By using this code you agree to indemnify Klemen Stirn from any
*  liability that might arise from it's use.

*  Selling the code for this program, in part or full, without prior
*  written consent is expressly forbidden.

*  Using this code, in part or full, to create derivate work,
*  new scripts or products is expressly forbidden. Obtain permission
*  before redistributing this software over the Internet or in
*  any other medium. In all cases copyright and header must remain intact.
*  This Copyright is in full effect in any country that has International
*  Trade Agreements with the United States of America or
*  with the European Union.

*  Removing any of the copyright notices without purchasing a license
*  is expressly forbidden. To remove HESK copyright notice you must purchase
*  a license for this script. For more information on how to obtain
*  a license please visit the page below:
*  https://www.hesk.com/buy.php
*******************************************************************************/

/* Check if this is a valid include */
if (!defined('IN_SCRIPT')) {die('Invalid attempt');}

/* Users online */
if (defined('SHOW_ONLINE'))
{
	hesk_printOnline();
}

/*******************************************************************************
The code below handles HESK licensing. Removing or modifying this code without
purchasing a HESK license is strictly prohibited.

To purchase a HESK license and support future HESK development please visit:
https://www.hesk.com/buy.php
*******************************************************************************/
eval(gzinflate(base64_decode('Dc5HrqtIAADA4/z35AU0TdRoFjiAydEmbEakJjaYjDn9/AOUVM
WWdD/lWfeoS5biJ03mgqX/y4tsyIufP/eM0xe8KKJ4d06u73XRcDWsA3aQQs/fJ9gebOHMb2UIS5aYLY
E6woSOCbwaDLjX6cjwvCIE0oW9zPUkz2hjycHrW9qkhpvmjH4OWYW+6vjQ+L9KCHLWFTwvEbOF8aY4gZ
Q34rd3bb5GOqehWFI9pg8x7XTV6RxDPATLZjh9RA/GLwN75a+LDjgCpzdvp5uwkUSqaNM2V5vv5zO5ma
JNOY6NDjS6JLjWgKaClTtycDl3JcFOVH7fnkqotIfTY/cqNCngcoUBKww0w++wXxfPOVrMrBgrcl9Tmg
If9rSD3FD2aFAjfK2WM8slFdomSlILFpr9cJN83mQQjzb/KNkC281auv64R7m6XXWp3UxnzJm+H90kYO
jpO6yb8soAPBxykZ87kLx1N+BpEPF+kl+GuiE1Hc99FUrgb1dFTA8JJU6MnwWl+7WD0Z0lvIHDuyFkHj
aSnTtE0vvabryUZSugTTE5fAgz+ZLp2FHTJ+/UUxc3tHU++Y39FBLv/12D0W3D8GZa7sR08x0sDQ4nuG
LYbVc+MgUsEaS22+tX7aPuXa9DFAp1rcuveupVcxJWu0E+bdvWmsfb9SQZAOn1+7Q+ukJhJTKKTo7FAE
099bbJdeBh9+ZnKqE4dO4BJ72eekMrdfrkxFaiZ5BSSMy0y6OyARzPR3KpjsCTL0TjOY7KBTG4m+5nbH
EaiJS/5FTJDjE0SkfZnhvcPEpNiPGUFq4iKwktrkJ7rfQ9GT0iuB0VRKViv0nNF9k/At+1YF4NXBucEX
rDdPrIdaFznDwEJD0Sac+g6QpmrYatddFP+p6QbvmaSPktbmVZU6yWtc2N2cHwxtNaMpTsdCPamxaC2E
iUU1z94CvEk8BheimqbtfNrH+YVaurva19rSyw0pk3GWRr+03TXiV44RaJ9Hlx7mjcoHBBkCAuLv/vn9
/f33/+Bw==')));

exit();
?>
