import{d as W,L as H,c as I,o as g,w as o,a,b as t,u as m,g as U,e as b,t as n,f as y,h as S,F as X,r as E,x as V,W as M}from"./app-BKBIjJ8s.js";import{_ as Y}from"./index-BRmZJ9Xg.js";import{_ as f}from"./Select.vue_vue_type_script_setup_true_lang-BhFDqIyF.js";import"./LocationInput.vue_vue_type_style_index_0_scoped_0fe2f881_lang-uzdB4u8v.js";import{R as z,C as q,_ as G}from"./AppLayout.vue_vue_type_script_setup_true_lang-8Bdy8szE.js";import{_ as p,a as x}from"./CardContent.vue_vue_type_script_setup_true_lang-DTZeO7bg.js";import{_ as D,a as P}from"./CardTitle.vue_vue_type_script_setup_true_lang-CMTFMfyV.js";import{P as J}from"./printer-D_zr8QPy.js";import{D as K,C as Q}from"./dollar-sign-COqBP7ff.js";import{C as Z}from"./circle-alert-Bci8eSI5.js";import"./DropdownMenuTrigger.vue_vue_type_script_setup_true_lang-DQ7D_30s.js";import"./createLucideIcon-CHp4SUBy.js";import"./RovingFocusGroup-BuaLcvw9.js";import"./VisuallyHidden-bjmLQ8MD.js";import"./AppLogoIcon.vue_vue_type_script_setup_true_lang-BX0trGSH.js";const tt={class:"flex items-center justify-between mb-6"},et={class:"grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-6 mb-8"},at={class:"flex items-center justify-between"},rt={class:"text-2xl font-bold text-gray-900 dark:text-gray-100"},st={class:"p-3 bg-green-100 dark:bg-green-900/20 rounded-full"},ot={class:"flex items-center justify-between"},dt={class:"text-2xl font-bold text-gray-900 dark:text-gray-100"},lt={class:"p-3 bg-blue-100 dark:bg-blue-900/20 rounded-full"},nt={class:"flex items-center justify-between"},it={class:"text-2xl font-bold text-gray-900 dark:text-gray-100"},mt={class:"p-3 bg-green-100 dark:bg-green-900/20 rounded-full"},ut={class:"flex items-center justify-between"},pt={class:"text-2xl font-bold text-gray-900 dark:text-gray-100"},xt={class:"p-3 bg-red-100 dark:bg-red-900/20 rounded-full"},gt={class:"flex items-center justify-between"},ct={class:"text-2xl font-bold text-gray-900 dark:text-gray-100"},yt={class:"p-3 bg-purple-100 dark:bg-purple-900/20 rounded-full"},ft={class:"grid grid-cols-1 md:grid-cols-4 gap-4"},bt={class:"overflow-x-auto"},_t={class:"w-full"},vt={class:"py-3 px-4 text-sm text-gray-900 dark:text-gray-100"},ht={class:"py-3 px-4 text-sm text-gray-900 dark:text-gray-100"},kt={class:"py-3 px-4"},wt={class:"text-sm font-medium text-gray-900 dark:text-gray-100"},Tt={key:0,class:"text-sm text-gray-500 dark:text-gray-400"},Ct={class:"py-3 px-4 text-right text-sm text-gray-900 dark:text-gray-100"},$t={class:"py-3 px-4 text-right text-sm text-gray-900 dark:text-gray-100"},St={class:"py-3 px-4 text-right text-sm text-gray-900 dark:text-gray-100"},Vt={class:"py-3 px-4 text-right text-sm text-gray-900 dark:text-gray-100"},zt={class:"py-3 px-4 text-right text-sm text-gray-900 dark:text-gray-100"},Dt={class:"py-3 px-4 text-right text-sm text-gray-900 dark:text-gray-100"},Pt={class:"py-3 px-4"},At={key:0,class:"text-center py-12"},Rt={class:"mx-auto h-12 w-12 text-gray-400"},Gt=W({__name:"Transactions",props:{transactions:{},customers:{},summary:{},filters:{}},setup(A){const u=A,l=H({...u.filters}),R=[{title:"Sales",href:"/sales/transactions"},{title:"Transactions",href:"/sales/transactions"}],_=[{value:"week",label:"Last Week"},{value:"month",label:"Last Month"},{value:"year",label:"Last Year"}],v=[{value:"created_at",label:"Date"},{value:"total_amount",label:"Amount"},{value:"payment_status",label:"Status"}],h=[{value:"desc",label:"Descending"},{value:"asc",label:"Ascending"}],c=()=>{M.get("/sales/transactions",l,{preserveState:!0,preserveScroll:!0})},i=d=>new Intl.NumberFormat("en-PH",{style:"currency",currency:"PHP",minimumFractionDigits:2}).format(d),k=d=>new Date(d).toLocaleDateString("en-PH",{year:"numeric",month:"short",day:"numeric",hour:"2-digit",minute:"2-digit"}),B=d=>{switch(d){case"Completed":return"bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-400";case"Canceled":return"bg-red-100 text-red-800 dark:bg-red-900/20 dark:text-red-400";case"Refunded":return"bg-gray-100 text-gray-800 dark:bg-gray-900/20 dark:text-gray-400";default:return"bg-gray-100 text-gray-800 dark:bg-gray-900/20 dark:text-gray-400"}},O=()=>{var w,T,C,$;const d=window.open("","_blank");if(!d)return;const e=new Date().toLocaleDateString("en-PH",{year:"numeric",month:"long",day:"numeric",hour:"2-digit",minute:"2-digit"}),r=((w=_.find(s=>s.value===l.period))==null?void 0:w.label)||l.period,j=l.customer==="all"?"All Customers":((T=u.customers.find(s=>s.value===l.customer))==null?void 0:T.label)||l.customer,F=((C=v.find(s=>s.value===l.sort_by))==null?void 0:C.label)||l.sort_by,L=(($=h.find(s=>s.value===l.sort_order))==null?void 0:$.label)||l.sort_order,N=`
    <!DOCTYPE html>
    <html>
    <head>
      <title>Sales Transactions Report - PayTrack</title>
      <style>
        @media print {
          body { margin: 0; padding: 20px; font-family: Arial, sans-serif; }
          .no-print { display: none !important; }
          table { width: 100%; border-collapse: collapse; margin-top: 20px; }
          th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
          th { background-color: #f8f9fa; font-weight: bold; }
          .header { text-align: center; margin-bottom: 30px; }
          .summary { margin-bottom: 20px; }
          .summary-grid { display: grid; grid-template-columns: repeat(5, 1fr); gap: 15px; margin-bottom: 20px; }
          .summary-item { border: 1px solid #ddd; padding: 10px; text-align: center; }
          .summary-label { font-size: 12px; color: #666; margin-bottom: 5px; }
          .summary-value { font-size: 16px; font-weight: bold; }
          .filters { margin-bottom: 20px; padding: 10px; background-color: #f8f9fa; border-radius: 5px; }
          .filters h3 { margin: 0 0 10px 0; font-size: 14px; }
          .filters p { margin: 5px 0; font-size: 12px; }
          .status-completed { background-color: #d4edda; color: #155724; padding: 2px 6px; border-radius: 3px; font-size: 11px; }
          .status-canceled { background-color: #f8d7da; color: #721c24; padding: 2px 6px; border-radius: 3px; font-size: 11px; }
          .status-cancelled { background-color: #f8d7da; color: #721c24; padding: 2px 6px; border-radius: 3px; font-size: 11px; }
          .status-refunded { background-color: #e2e3e5; color: #383d41; padding: 2px 6px; border-radius: 3px; font-size: 11px; }
          .footer { margin-top: 30px; text-align: center; font-size: 12px; color: #666; }
        }
        @media screen {
          body { font-family: Arial, sans-serif; padding: 20px; }
          .no-print { display: block; }
          table { width: 100%; border-collapse: collapse; margin-top: 20px; }
          th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
          th { background-color: #f8f9fa; font-weight: bold; }
          .header { text-align: center; margin-bottom: 30px; }
          .summary { margin-bottom: 20px; }
          .summary-grid { display: grid; grid-template-columns: repeat(5, 1fr); gap: 15px; margin-bottom: 20px; }
          .summary-item { border: 1px solid #ddd; padding: 10px; text-align: center; }
          .summary-label { font-size: 12px; color: #666; margin-bottom: 5px; }
          .summary-value { font-size: 16px; font-weight: bold; }
          .filters { margin-bottom: 20px; padding: 10px; background-color: #f8f9fa; border-radius: 5px; }
          .filters h3 { margin: 0 0 10px 0; font-size: 14px; }
          .filters p { margin: 5px 0; font-size: 12px; }
          .status-completed { background-color: #d4edda; color: #155724; padding: 2px 6px; border-radius: 3px; font-size: 11px; }
          .status-canceled { background-color: #f8d7da; color: #721c24; padding: 2px 6px; border-radius: 3px; font-size: 11px; }
          .status-cancelled { background-color: #f8d7da; color: #721c24; padding: 2px 6px; border-radius: 3px; font-size: 11px; }
          .status-refunded { background-color: #e2e3e5; color: #383d41; padding: 2px 6px; border-radius: 3px; font-size: 11px; }
          .footer { margin-top: 30px; text-align: center; font-size: 12px; color: #666; }
          .print-button { 
            position: fixed; 
            top: 20px; 
            right: 20px; 
            background: #007bff; 
            color: white; 
            border: none; 
            padding: 10px 20px; 
            border-radius: 5px; 
            cursor: pointer; 
            font-size: 14px;
          }
        }
      </style>
    </head>
    <body>
      <button class="print-button no-print" onclick="window.print()">Print Report</button>
      
      <div class="header">
        <h1>PayTrack - Sales Transactions Report</h1>
        <p>Generated on: ${e}</p>
      </div>

      <div class="summary">
        <h2>Summary</h2>
        <div class="summary-grid">
          <div class="summary-item">
            <div class="summary-label">Total Revenue</div>
            <div class="summary-value">${i(u.summary.total_revenue)}</div>
          </div>
          <div class="summary-item">
            <div class="summary-label">Total Transactions</div>
            <div class="summary-value">${u.summary.total_transactions}</div>
          </div>
          <div class="summary-item">
            <div class="summary-label">Completed</div>
            <div class="summary-value">${u.summary.completed_transactions}</div>
          </div>
          <div class="summary-item">
            <div class="summary-label">Canceled</div>
            <div class="summary-value">${u.summary.pending_transactions}</div>
          </div>
          <div class="summary-item">
            <div class="summary-label">Avg Order Value</div>
            <div class="summary-value">${i(u.summary.average_order_value)}</div>
          </div>
        </div>
      </div>

      <div class="filters">
        <h3>Report Filters</h3>
        <p><strong>Time Period:</strong> ${r}</p>
        <p><strong>Customer:</strong> ${j}</p>
        <p><strong>Sort By:</strong> ${F}</p>
        <p><strong>Sort Order:</strong> ${L}</p>
      </div>

      <table>
        <thead>
          <tr>
            <th>Date</th>
            <th>Invoice # - Reference</th>
            <th>Customer</th>
            <th>Cash</th>
            <th>W/holding TAX 1%</th>
            <th>TAX 5%</th>
            <th>Sale - Non vat total</th>
            <th>VAT</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody>
          ${u.transactions.map(s=>`
            <tr>
              <td>${k(s.transaction_date)}</td>
              <td>${s.invoice_number}</td>
              <td>
                ${s.customer_name}
                ${s.company_name?`<br><small>${s.company_name}</small>`:""}
              </td>
              <td>${i(s.cash_amount)}</td>
              <td>${i(s.withholding_tax)}</td>
              <td>${i(s.tax_5_percent)}</td>
              <td>${i(s.sale_non_vat_total)}</td>
              <td>${i(s.vat_amount)}</td>
              <td><span class="status-${s.status.toLowerCase()}">${s.status}</span></td>
            </tr>
          `).join("")}
        </tbody>
      </table>

      <div class="footer">
        <p>This report was generated by PayTrack Sales Management System</p>
        <p>For questions or support, please contact your system administrator</p>
      </div>
    </body>
    </html>
  `;d.document.write(N),d.document.close(),d.focus()};return(d,e)=>(g(),I(G,{breadcrumbs:R},{default:o(()=>[a(m(U),{title:"Sales Transactions"}),t("div",tt,[e[5]||(e[5]=t("div",null,[t("h1",{class:"text-2xl font-bold"},"Sales Transactions"),t("p",{class:"text-sm text-gray-600 dark:text-gray-400"},"View and manage all invoice transactions")],-1)),a(m(Y),{onClick:O,class:"flex items-center gap-2"},{default:o(()=>[a(m(J),{class:"w-4 h-4"}),e[4]||(e[4]=b(" Print Report "))]),_:1,__:[4]})]),t("div",et,[a(p,null,{default:o(()=>[a(x,{class:"p-6"},{default:o(()=>[t("div",at,[t("div",null,[e[6]||(e[6]=t("p",{class:"text-sm font-medium text-gray-600 dark:text-gray-400"},"Total Revenue",-1)),t("p",rt,n(i(d.summary.total_revenue)),1)]),t("div",st,[a(m(K),{class:"w-6 h-6 text-green-600"})])])]),_:1})]),_:1}),a(p,null,{default:o(()=>[a(x,{class:"p-6"},{default:o(()=>[t("div",ot,[t("div",null,[e[7]||(e[7]=t("p",{class:"text-sm font-medium text-gray-600 dark:text-gray-400"},"Total Transactions",-1)),t("p",dt,n(d.summary.total_transactions),1)]),t("div",lt,[a(m(z),{class:"w-6 h-6 text-blue-600"})])])]),_:1})]),_:1}),a(p,null,{default:o(()=>[a(x,{class:"p-6"},{default:o(()=>[t("div",nt,[t("div",null,[e[8]||(e[8]=t("p",{class:"text-sm font-medium text-gray-600 dark:text-gray-400"},"Completed",-1)),t("p",it,n(d.summary.completed_transactions),1)]),t("div",mt,[a(m(Q),{class:"w-6 h-6 text-green-600"})])])]),_:1})]),_:1}),a(p,null,{default:o(()=>[a(x,{class:"p-6"},{default:o(()=>[t("div",ut,[t("div",null,[e[9]||(e[9]=t("p",{class:"text-sm font-medium text-gray-600 dark:text-gray-400"},"Canceled",-1)),t("p",pt,n(d.summary.pending_transactions),1)]),t("div",xt,[a(m(Z),{class:"w-6 h-6 text-red-600"})])])]),_:1})]),_:1}),a(p,null,{default:o(()=>[a(x,{class:"p-6"},{default:o(()=>[t("div",gt,[t("div",null,[e[10]||(e[10]=t("p",{class:"text-sm font-medium text-gray-600 dark:text-gray-400"},"Avg Order Value",-1)),t("p",ct,n(i(d.summary.average_order_value)),1)]),t("div",yt,[a(m(q),{class:"w-6 h-6 text-purple-600"})])])]),_:1})]),_:1})]),a(p,{class:"mb-6"},{default:o(()=>[a(D,null,{default:o(()=>[a(P,null,{default:o(()=>e[11]||(e[11]=[b("Filters")])),_:1,__:[11]})]),_:1}),a(x,null,{default:o(()=>[t("div",ft,[t("div",null,[e[12]||(e[12]=t("label",{class:"block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"},"Time Period",-1)),a(m(f),{modelValue:l.period,"onUpdate:modelValue":[e[0]||(e[0]=r=>l.period=r),c],options:_},null,8,["modelValue"])]),t("div",null,[e[13]||(e[13]=t("label",{class:"block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"},"Customer",-1)),a(m(f),{modelValue:l.customer,"onUpdate:modelValue":[e[1]||(e[1]=r=>l.customer=r),c],options:d.customers},null,8,["modelValue","options"])]),t("div",null,[e[14]||(e[14]=t("label",{class:"block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"},"Sort By",-1)),a(m(f),{modelValue:l.sort_by,"onUpdate:modelValue":[e[2]||(e[2]=r=>l.sort_by=r),c],options:v},null,8,["modelValue"])]),t("div",null,[e[15]||(e[15]=t("label",{class:"block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"},"Sort Order",-1)),a(m(f),{modelValue:l.sort_order,"onUpdate:modelValue":[e[3]||(e[3]=r=>l.sort_order=r),c],options:h},null,8,["modelValue"])])])]),_:1})]),_:1}),a(p,null,{default:o(()=>[a(D,null,{default:o(()=>[a(P,null,{default:o(()=>e[16]||(e[16]=[b("Invoice Transactions")])),_:1,__:[16]})]),_:1}),a(x,null,{default:o(()=>[t("div",bt,[t("table",_t,[e[17]||(e[17]=t("thead",null,[t("tr",{class:"border-b dark:border-gray-700"},[t("th",{class:"text-left py-3 px-4 font-medium text-gray-900 dark:text-gray-100"},"Date"),t("th",{class:"text-left py-3 px-4 font-medium text-gray-900 dark:text-gray-100"},"Invoice # - Reference"),t("th",{class:"text-left py-3 px-4 font-medium text-gray-900 dark:text-gray-100"},"Customer"),t("th",{class:"text-right py-3 px-4 font-medium text-gray-900 dark:text-gray-100"},"Cash"),t("th",{class:"text-right py-3 px-4 font-medium text-gray-900 dark:text-gray-100"},"W/holding TAX 1%"),t("th",{class:"text-right py-3 px-4 font-medium text-gray-900 dark:text-gray-100"},"TAX 5%"),t("th",{class:"text-right py-3 px-4 font-medium text-gray-900 dark:text-gray-100"},"Sale - Non vat total"),t("th",{class:"text-right py-3 px-4 font-medium text-gray-900 dark:text-gray-100"},"VAT"),t("th",{class:"text-right py-3 px-4 font-medium text-gray-900 dark:text-gray-100"},"Running Balance"),t("th",{class:"text-left py-3 px-4 font-medium text-gray-900 dark:text-gray-100"},"Status")])],-1)),t("tbody",null,[(g(!0),y(X,null,E(d.transactions,r=>(g(),y("tr",{key:r.id,class:V(["border-b dark:border-gray-700",r.status==="Canceled"?"bg-red-50 dark:bg-red-900/20 hover:bg-red-100 dark:hover:bg-red-900/30":"hover:bg-gray-50 dark:hover:bg-gray-800"])},[t("td",vt,n(k(r.transaction_date)),1),t("td",ht,n(r.invoice_number),1),t("td",kt,[t("div",null,[t("div",wt,n(r.customer_name),1),r.company_name?(g(),y("div",Tt,n(r.company_name),1)):S("",!0)])]),t("td",Ct,n(i(r.cash_amount)),1),t("td",$t,n(i(r.withholding_tax)),1),t("td",St,n(i(r.tax_5_percent)),1),t("td",Vt,n(i(r.sale_non_vat_total)),1),t("td",zt,n(i(r.vat_amount)),1),t("td",Dt,n(i(r.running_balance)),1),t("td",Pt,[t("span",{class:V([B(r.status),"inline-flex px-2 py-1 text-xs font-semibold rounded-full"])},n(r.status),3)])],2))),128))])]),d.transactions.length===0?(g(),y("div",At,[t("div",Rt,[a(m(z),{class:"h-12 w-12"})]),e[18]||(e[18]=t("h3",{class:"mt-2 text-sm font-medium text-gray-900 dark:text-gray-100"},"No invoice transactions found",-1)),e[19]||(e[19]=t("p",{class:"mt-1 text-sm text-gray-500 dark:text-gray-400"},"Try adjusting your filters to see more results.",-1))])):S("",!0)])]),_:1})]),_:1})]),_:1}))}});export{Gt as default};
