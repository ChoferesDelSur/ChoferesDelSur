import{e as m,h as p,d as s,u as o,w as u,F as c,o as f,H as b,b as a,n as x,k as V,g as v}from"./app-ns4U5E9T.js";import{A as w,_}from"./PrimaryButton-CHOGfWNC.js";import{_ as d,a as i}from"./InputLabel-YoasKW9Z.js";const g={class:""},y={class:""},k={class:""},M={class:""},P={class:"mt-4"},q={class:"flex items-center justify-end mt-4"},B={__name:"RegisterFT",setup(U){const l=m({nombre:"",apellidoP:"",apellidoM:"",usuario:"",password:""}),n=()=>{try{const r=route("registrarse");console.log("Request URL:",r),l.post(r)}catch(r){console.log(r)}};return(r,e)=>(f(),p(c,null,[s(o(b),{title:"Registrarse"}),s(w,null,{default:u(()=>[e[6]||(e[6]=a("div",{class:"flex flex-col items-center"},[a("i",{class:"fa fa-user-circle text-6xl text-blue-600","aria-hidden":"true"}),a("h2",{class:"text-black text-2xl text-center font-semibold p-5"},"Registrarse"),a("div",{class:"p-4 mb-4 text-sm text-justify rounded-lg"},[a("span",{class:""},'Bienvenido al sistema de control y gestión de la empresa "Sociedad Cooperativa de Choferes del Sur S.C.L.". Al ser el primer ingreso al sistema es necesario que cree las credenciales del usuario Administrador.')])],-1)),a("form",{onSubmit:v(n,["prevent"])},[a("div",g,[s(d,{for:"nombre",value:"Nombre(s)"}),s(i,{id:"nombre",modelValue:o(l).nombre,"onUpdate:modelValue":e[0]||(e[0]=t=>o(l).nombre=t),type:"text",class:"mt-1 block w-full",required:"",autofocus:"",autocomplete:"nombre"},null,8,["modelValue"])]),a("div",y,[s(d,{for:"apellidoP",value:"Apellido Paterno"}),s(i,{id:"apellidoP",modelValue:o(l).apellidoP,"onUpdate:modelValue":e[1]||(e[1]=t=>o(l).apellidoP=t),type:"text",class:"mt-1 block w-full",required:"",autocomplete:"apellidoP"},null,8,["modelValue"])]),a("div",k,[s(d,{for:"apellidoM",value:"Apellido Materno"}),s(i,{id:"apellidoM",modelValue:o(l).apellidoM,"onUpdate:modelValue":e[2]||(e[2]=t=>o(l).apellidoM=t),type:"text",class:"mt-1 block w-full",required:"",autocomplete:"apellidoM"},null,8,["modelValue"])]),a("div",M,[s(d,{for:"usuario",value:"Usuario"}),s(i,{id:"usuario",modelValue:o(l).usuario,"onUpdate:modelValue":e[3]||(e[3]=t=>o(l).usuario=t),type:"text",class:"mt-1 block w-full",required:"",autocomplete:"usuario"},null,8,["modelValue"])]),a("div",P,[s(d,{for:"password",value:"Password"}),s(i,{id:"password",modelValue:o(l).password,"onUpdate:modelValue":e[4]||(e[4]=t=>o(l).password=t),type:"password",class:"mt-1 block w-full",required:"",autocomplete:"password"},null,8,["modelValue"])]),a("div",q,[s(_,{class:x(["ml-4",{"opacity-25":o(l).processing}]),disabled:o(l).processing},{default:u(()=>e[5]||(e[5]=[V(" Registrarse ")])),_:1},8,["class","disabled"])])],32)]),_:1})],64))}};export{B as default};