/**
 * Dynamic Price Updater V5.0
 * @copyright Dan Parry (Chrome) / Erik Kerkhoven (Design75) / mc12345678 / torvista
 * @original author Dan Parry (Chrome)
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 * @version $Id: 2023 Mar 10
 */
try {
    init(); // DPU javascript function
} catch(dpu_err) { // error log
    console.error('DPU catch error (javascript error/init not found):', dpu_err.stack);
}
