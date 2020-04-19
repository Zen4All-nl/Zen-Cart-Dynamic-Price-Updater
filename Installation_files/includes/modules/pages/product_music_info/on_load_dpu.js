try {
  init();
} catch(dpu_err) { 
  console.log('DPU catch error:', dpu_err.stack);
}