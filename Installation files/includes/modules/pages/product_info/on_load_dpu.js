try { 
  init();
} catch(dpu_err) { 
  console.log(dpu_err.stack);
}