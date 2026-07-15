(async ()=>{
  const url = 'http://localhost/RENTALIN_TENSTING%20-%20Copy/src/pages/register/index.php';
  try{
    const res = await fetch(url, { method: 'GET' });
    console.log('STATUS:' + res.status);
    const text = await res.text();
    console.log('BODY_HEAD:');
    console.log(text.slice(0,800));
    process.exit(0);
  }catch(e){
    console.error('ERROR:', e.message);
    process.exit(1);
  }
})();
