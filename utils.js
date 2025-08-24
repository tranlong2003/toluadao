// server/utils.js
function normalize(type, value) {
  let v = (value || '').trim();
  if (type === 'phone') {
    v = v.replace(/\D/g, '');
    if (v.startsWith('0')) v = '84' + v.slice(1);
    if (!v.startsWith('84')) v = '84' + v; // giả sử VN
    v = '+' + v;
  } else if (type === 'bank') {
    v = v.replace(/\s/g, '').replace(/\D/g, '').replace(/^0+/, ''); // bỏ khoảng trắng & số 0 đầu
  } else if (type === 'link') {
    v = v.toLowerCase();
  }
  return v;
}

function riskScore(evidences, communityApproved) {
  const n = evidences.length;
  const recent = evidences.some(e => e.published_at && (Date.now() - new Date(e.published_at).getTime()) < 90*86400000);
  const score = n*0.8 + communityApproved*1.2 + (recent ? 1 : 0);
  let level = 'An toàn';
  if (score > 1) level = 'Nghi vấn';
  if (score > 3) level = 'Nguy cơ cao';
  return { score, level };
}

module.exports = { normalize, riskScore };
