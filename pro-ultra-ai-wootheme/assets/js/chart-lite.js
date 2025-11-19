(function(){
'use strict';
function hexToRgba(hex, alpha){
  if(!hex){ return 'rgba(0,0,0,'+alpha+')'; }
  const c = hex.replace('#','');
  const bigint = parseInt(c.length === 3 ? c.split('').map(h=>h+h).join('') : c, 16);
  const r = (bigint >> 16) & 255;
  const g = (bigint >> 8) & 255;
  const b = bigint & 255;
  return `rgba(${r},${g},${b},${alpha})`;
}
class SimpleChart{
  constructor(ctx, config){
    this.ctx = ctx;
    this.config = config;
    this.height = ctx.canvas.height || 260;
    this.width = ctx.canvas.width || ctx.canvas.clientWidth || 320;
    ctx.canvas.width = this.width;
    ctx.canvas.height = this.height;
    this.draw();
  }
  destroy(){
    const canvas = this.ctx.canvas;
    const clone = canvas.cloneNode(true);
    canvas.parentNode.replaceChild(clone, canvas);
  }
  draw(){
    const {type, data} = this.config;
    if(type === 'line'){ this.drawLine(); }
    else if(type === 'bar'){ this.drawBar(); }
    else if(type === 'doughnut'){ this.drawDoughnut(); }
  }
  maxValue(values){
    return Math.max.apply(null, values.concat([1]));
  }
  drawLine(){
    const {labels = [], datasets = []} = this.config.data;
    const ctx = this.ctx;
    const padding = 32;
    const steps = Math.max(labels.length - 1, 1);
    ctx.clearRect(0,0,this.width,this.height);
    datasets.forEach((set)=>{
      const values = set.data || [];
      const max = this.maxValue(values);
      ctx.beginPath();
      values.forEach((val, idx)=>{
        const x = padding + (this.width - padding * 2) * (idx/steps);
        const y = this.height - padding - (val/max)*(this.height - padding*2);
        if(idx===0){ ctx.moveTo(x,y); } else { ctx.lineTo(x,y); }
      });
      ctx.strokeStyle = set.borderColor || '#555';
      ctx.lineWidth = 2;
      ctx.stroke();
      if(set.fill){
        ctx.lineTo(this.width - padding, this.height - padding);
        ctx.lineTo(padding, this.height - padding);
        ctx.closePath();
        ctx.fillStyle = set.backgroundColor || hexToRgba(set.borderColor || '#555',0.15);
        ctx.fill();
      }
    });
  }
  drawBar(){
    const {labels = [], datasets = []} = this.config.data;
    const data = datasets[0] || {data:[]};
    const ctx = this.ctx;
    const padding = 32;
    const barWidth = (this.width - padding*2) / Math.max(labels.length,1) - 8;
    const max = this.maxValue(data.data || []);
    ctx.clearRect(0,0,this.width,this.height);
    (data.data || []).forEach((val, idx)=>{
      const x = padding + idx * (barWidth + 8);
      const h = (val/max)*(this.height - padding*2);
      const y = this.height - padding - h;
      ctx.fillStyle = data.backgroundColor || '#3298DC';
      ctx.fillRect(x, y, barWidth, h);
    });
  }
  drawDoughnut(){
    const {datasets = [], labels=[]} = this.config.data;
    const data = datasets[0] || {data:[]};
    const total = (data.data || []).reduce((a,b)=>a+Number(b||0),0) || 1;
    const ctx = this.ctx;
    const radius = Math.min(this.width, this.height)/2 - 10;
    let start = -Math.PI/2;
    ctx.clearRect(0,0,this.width,this.height);
    (data.data || []).forEach((val, idx)=>{
      const angle = (val/total)*Math.PI*2;
      ctx.beginPath();
      ctx.moveTo(this.width/2, this.height/2);
      ctx.arc(this.width/2, this.height/2, radius, start, start+angle);
      ctx.closePath();
      ctx.fillStyle = (data.backgroundColor && data.backgroundColor[idx]) || '#6C63FF';
      ctx.fill();
      start += angle;
    });
    ctx.beginPath();
    ctx.fillStyle = '#fff';
    ctx.arc(this.width/2, this.height/2, radius*0.55, 0, Math.PI*2);
    ctx.fill();
    ctx.fillStyle = '#444';
    ctx.font = '12px sans-serif';
    ctx.textAlign = 'center';
    ctx.fillText(labels[0] || '', this.width/2, this.height/2);
  }
}
window.Chart = SimpleChart;
})();
