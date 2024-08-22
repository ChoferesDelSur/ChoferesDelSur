import{J as _}from"./app-ChVPO-hl.js";class l{constructor(e,t,r){this.name=e,this.definition=t,this.bindings=t.bindings??{},this.wheres=t.wheres??{},this.config=r}get template(){const e=`${this.origin}/${this.definition.uri}`.replace(/\/+$/,"");return e===""?"/":e}get origin(){return this.config.absolute?this.definition.domain?`${this.config.url.match(/^\w+:\/\//)[0]}${this.definition.domain}${this.config.port?`:${this.config.port}`:""}`:this.config.url:""}get parameterSegments(){var e;return((e=this.template.match(/{[^}?]+\??}/g))==null?void 0:e.map(t=>({name:t.replace(/{|\??}/g,""),required:!/\?}$/.test(t)})))??[]}matchesUrl(e){if(!this.definition.methods.includes("GET"))return!1;const t=this.template.replace(/(\/?){([^}?]*)(\??)}/g,(n,c,d,p)=>{var o;const f=`(?<${d}>${((o=this.wheres[d])==null?void 0:o.replace(/(^\^)|(\$$)/g,""))||"[^/?]+"})`;return p?`(${c}${f})?`:`${c}${f}`}).replace(/^\w+:\/\//,""),[r,i]=e.replace(/^\w+:\/\//,"").split("?"),s=new RegExp(`^${t}/?$`).exec(decodeURI(r));if(s){for(const n in s.groups)s.groups[n]=typeof s.groups[n]=="string"?decodeURIComponent(s.groups[n]):s.groups[n];return{params:s.groups,query:_.parse(i)}}return!1}compile(e){return this.parameterSegments.length?this.template.replace(/{([^}?]+)(\??)}/g,(r,i,s)=>{if(!s&&[null,void 0].includes(e[i]))throw new Error(`Ziggy error: '${i}' parameter is required for route '${this.name}'.`);if(this.wheres[i]&&!new RegExp(`^${s?`(${this.wheres[i]})?`:this.wheres[i]}$`).test(e[i]??""))throw new Error(`Ziggy error: '${i}' parameter does not match required format '${this.wheres[i]}' for route '${this.name}'.`);return encodeURI(e[i]??"").replace(/%7C/g,"|").replace(/%25/g,"%").replace(/\$/g,"%24")}).replace(`${this.origin}//`,`${this.origin}/`).replace(/\/+$/,""):this.template}}class $ extends String{constructor(e,t,r=!0,i){if(super(),this._config=i??(typeof Ziggy<"u"?Ziggy:globalThis==null?void 0:globalThis.Ziggy),this._config={...this._config,absolute:r},e){if(!this._config.routes[e])throw new Error(`Ziggy error: route '${e}' is not in the route list.`);this._route=new l(e,this._config.routes[e],this._config),this._params=this._parse(t)}}toString(){const e=Object.keys(this._params).filter(t=>!this._route.parameterSegments.some(({name:r})=>r===t)).filter(t=>t!=="_query").reduce((t,r)=>({...t,[r]:this._params[r]}),{});return this._route.compile(this._params)+_.stringify({...e,...this._params._query},{addQueryPrefix:!0,arrayFormat:"indices",encodeValuesOnly:!0,skipNulls:!0,encoder:(t,r)=>typeof t=="boolean"?Number(t):r(t)})}_unresolve(e){e?this._config.absolute&&e.startsWith("/")&&(e=this._location().host+e):e=this._currentUrl();let t={};const[r,i]=Object.entries(this._config.routes).find(([s,n])=>t=new l(s,n,this._config).matchesUrl(e))||[void 0,void 0];return{name:r,...t,route:i}}_currentUrl(){const{host:e,pathname:t,search:r}=this._location();return(this._config.absolute?e+t:t.replace(this._config.url.replace(/^\w*:\/\/[^/]+/,""),"").replace(/^\/+/,"/"))+r}current(e,t){const{name:r,params:i,query:s,route:n}=this._unresolve();if(!e)return r;const c=new RegExp(`^${e.replace(/\./g,"\\.").replace(/\*/g,".*")}$`).test(r);if([null,void 0].includes(t)||!c)return c;const d=new l(r,n,this._config);t=this._parse(t,d);const p={...i,...s};if(Object.values(t).every(o=>!o)&&!Object.values(p).some(o=>o!==void 0))return!0;const f=(o,h)=>Object.entries(o).every(([u,a])=>Array.isArray(a)&&Array.isArray(h[u])?a.every(m=>h[u].includes(m)):typeof a=="object"&&typeof h[u]=="object"&&a!==null&&h[u]!==null?f(a,h[u]):h[u]==a);return f(t,p)}_location(){var i,s,n;const{host:e="",pathname:t="",search:r=""}=typeof window<"u"?window.location:{};return{host:((i=this._config.location)==null?void 0:i.host)??e,pathname:((s=this._config.location)==null?void 0:s.pathname)??t,search:((n=this._config.location)==null?void 0:n.search)??r}}get params(){const{params:e,query:t}=this._unresolve();return{...e,...t}}has(e){return Object.keys(this._config.routes).includes(e)}_parse(e={},t=this._route){e??(e={}),e=["string","number"].includes(typeof e)?[e]:e;const r=t.parameterSegments.filter(({name:i})=>!this._config.defaults[i]);return Array.isArray(e)?e=e.reduce((i,s,n)=>r[n]?{...i,[r[n].name]:s}:typeof s=="object"?{...i,...s}:{...i,[s]:""},{}):r.length===1&&!e[r[0].name]&&(e.hasOwnProperty(Object.values(t.bindings)[0])||e.hasOwnProperty("id"))&&(e={[r[0].name]:e}),{...this._defaults(t),...this._substituteBindings(e,t)}}_defaults(e){return e.parameterSegments.filter(({name:t})=>this._config.defaults[t]).reduce((t,{name:r},i)=>({...t,[r]:this._config.defaults[r]}),{})}_substituteBindings(e,{bindings:t,parameterSegments:r}){return Object.entries(e).reduce((i,[s,n])=>{if(!n||typeof n!="object"||Array.isArray(n)||!r.some(({name:c})=>c===s))return{...i,[s]:n};if(!n.hasOwnProperty(t[s]))if(n.hasOwnProperty("id"))t[s]="id";else throw new Error(`Ziggy error: object passed as '${s}' parameter is missing route model binding key '${t[s]}'.`);return{...i,[s]:n[t[s]]}},{})}valueOf(){return this.toString()}}function y(g,e,t,r){const i=new $(g,e,t,r);return g?i.toString():i}export{y as r};