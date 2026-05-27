import{r as o}from"./index-CcAZ7C8a.js";function n(e,t=400){const[r,u]=o.useState(e);return o.useEffect(()=>{const s=setTimeout(()=>{u(e)},t);return()=>clearTimeout(s)},[e,t]),r}export{n as u};
