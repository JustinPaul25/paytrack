import{d as W,G as H,a as I,o as g,w as o,b as a,e as t,u as m,k as U,f as b,t as n,g as y,l as S,F as X,h as E,n as V,W as G}from"./app-WiUpeBwz.js";import{_ as M}from"./index-Cn_g6VzM.js";import{_ as f}from"./Select.vue_vue_type_script_setup_true_lang-C6cCdH76.js";import"./LocationInput.vue_vue_type_style_index_0_scoped_5601c0ff_lang-DTIP4i8X.js";import{D as Y,R as z,C as q,_ as J}from"./AppLayout.vue_vue_type_script_setup_true_lang-asCoNTCN.js";import{_ as p,a as x}from"./CardContent.vue_vue_type_script_setup_true_lang-C1Ro3lXn.js";import{a as D,_ as P}from"./CardTitle.vue_vue_type_script_setup_true_lang-CL8KIKUs.js";import{P as K}from"./printer-B2JEqeVr.js";import{C as Q}from"./circle-check-big-Bccf9EUT.js";import{C as Z}from"./circle-alert-DA-UBoTE.js";import"./DropdownMenuTrigger.vue_vue_type_script_setup_true_lang-BUpirRbf.js";import"./createLucideIcon-DXTUwZoB.js";import"./RovingFocusGroup-DA_UrK7F.js";import"./VisuallyHidden-CHKbz_EY.js";import"./AppLogoIcon.vue_vue_type_script_setup_true_lang-4xCIPFzj.js";const tt={class:"flex items-center justify-between mb-6"},et={class:"grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-6 mb-8"},at={class:"flex items-center justify-between"},st={class:"text-2xl font-bold text-gray-900 dark:text-gray-100"},rt={class:"p-3 bg-green-100 dark:bg-green-900/20 rounded-full"},ot={class:"flex items-center justify-between"},dt={class:"text-2xl font-bold text-gray-900 dark:text-gray-100"},lt={class:"p-3 bg-blue-100 dark:bg-blue-900/20 rounded-full"},nt={class:"flex items-center justify-between"},it={class:"text-2xl font-bold text-gray-900 dark:text-gray-100"},mt={class:"p-3 bg-green-100 dark:bg-green-900/20 rounded-full"},ut={class:"flex items-center justify-between"},pt={class:"text-2xl font-bold text-gray-900 dark:text-gray-100"},xt={class:"p-3 bg-red-100 dark:bg-red-900/20 rounded-full"},gt={class:"flex items-center justify-between"},ct={class:"text-2xl font-bold text-gray-900 dark:text-gray-100"},yt={class:"p-3 bg-purple-100 dark:bg-purple-900/20 rounded-full"},ft={class:"grid grid-cols-1 md:grid-cols-4 gap-4"},bt={class:"overflow-x-auto"},_t={class:"w-full"},vt={class:"py-3 px-4 text-sm text-gray-900 dark:text-gray-100"},ht={class:"py-3 px-4 text-sm text-gray-900 dark:text-gray-100"},kt={class:"py-3 px-4"},wt={class:"text-sm font-medium text-gray-900 dark:text-gray-100"},Tt={key:0,class:"text-sm text-gray-500 dark:text-gray-400"},Ct={class:"py-3 px-4 text-right text-sm text-gray-900 dark:text-gray-100"},$t={class:"py-3 px-4 text-right text-sm text-gray-900 dark:text-gray-100"},St={class:"py-3 px-4 text-right text-sm text-gray-900 dark:text-gray-100"},Vt={class:"py-3 px-4 text-right text-sm text-gray-900 dark:text-gray-100"},zt={class:"py-3 px-4 text-right text-sm text-gray-900 dark:text-gray-100"},Dt={class:"py-3 px-4 text-right text-sm text-gray-900 dark:text-gray-100"},Pt={class:"py-3 px-4"},At={key:0,class:"text-center py-12"},Rt={class:"mx-auto h-12 w-12 text-gray-400"},qt=W({__name:"Transactions",props:{transactions:{},customers:{},summary:{},filters:{}},setup(A){const u=A,l=H({...u.filters}),R=[{title:"Sales",href:"/sales/transactions"},{title:"Transactions",href:"/sales/transactions"}],_=[{value:"week",label:"Last Week"},{value:"month",label:"Last Month"},{value:"year",label:"Last Year"}],v=[{value:"created_at",label:"Date"},{value:"total_amount",label:"Amount"},{value:"payment_status",label:"Status"}],h=[{value:"desc",label:"Descending"},{value:"asc",label:"Ascending"}],c=()=>{G.get("/sales/transactions",l,{preserveState:!0,preserveScroll:!0})},i=d=>new Intl.NumberFormat("en-PH",{style:"currency",currency:"PHP",minimumFractionDigits:2}).format(d),k=d=>new Date(d).toLocaleDateString("en-PH",{year:"numeric",month:"short",day:"numeric",hour:"2-digit",minute:"2-digit"}),B=d=>{switch(d){case"Completed":return"bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-400";case"Canceled":return"bg-red-100 text-red-800 dark:bg-red-900/20 dark:text-red-400";case"Refunded":return"bg-gray-100 text-gray-800 dark:bg-gray-900/20 dark:text-gray-400";default:return"bg-gray-100 text-gray-800 dark:bg-gray-900/20 dark:text-gray-400"}},O=()=>{var w,T,C,$;const d=window.open("","_blank");if(!d)return;const e=new Date().toLocaleDateString("en-PH",{year:"numeric",month:"long",day:"numeric",hour:"2-digit",minute:"2-digit"}),s=((w=_.find(r=>r.value===l.period))==null?void 0:w.label)||l.period,j=l.customer==="all"?"All Customers":((T=u.customers.find(r=>r.value===l.customer))==null?void 0:T.label)||l.customer,F=((C=v.find(r=>r.value===l.sort_by))==null?void 0:C.label)||l.sort_by,N=(($=h.find(r=>r.value===l.sort_order))==null?void 0:$.label)||l.sort_order,L=`
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
        <p><strong>Time Period:</strong> ${s}</p>
        <p><strong>Customer:</strong> ${j}</p>
        <p><strong>Sort By:</strong> ${F}</p>
        <p><strong>Sort Order:</strong> ${N}</p>
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
          ${u.transactions.map(r=>`
            <tr>
              <td>${k(r.transaction_date)}</td>
              <td>${r.invoice_number}</td>
              <td>
                ${r.customer_name}
                ${r.company_name?`<br><small>${r.company_name}</small>`:""}
              </td>
              <td>${i(r.cash_amount)}</td>
              <td>${i(r.withholding_tax)}</td>
              <td>${i(r.tax_5_percent)}</td>
              <td>${i(r.sale_non_vat_total)}</td>
              <td>${i(r.vat_amount)}</td>
              <td><span class="status-${r.status.toLowerCase()}">${r.status}</span></td>
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
  `;d.document.write(L),d.document.close(),d.focus()};return(d,e)=>(g(),I(J,{breadcrumbs:R},{default:o(()=>[a(m(U),{title:"Sales Transactions"}),t("div",tt,[e[5]||(e[5]=t("div",null,[t("h1",{class:"text-2xl font-bold"},"Sales Transactions"),t("p",{class:"text-sm text-gray-600 dark:text-gray-400"},"View and manage all invoice transactions")],-1)),a(m(M),{onClick:O,class:"flex items-center gap-2"},{default:o(()=>[a(m(K),{class:"w-4 h-4"}),e[4]||(e[4]=b(" Print Report "))]),_:1,__:[4]})]),t("div",et,[a(p,null,{default:o(()=>[a(x,{class:"p-6"},{default:o(()=>[t("div",at,[t("div",null,[e[6]||(e[6]=t("p",{class:"text-sm font-medium text-gray-600 dark:text-gray-400"},"Total Revenue",-1)),t("p",st,n(i(d.summary.total_revenue)),1)]),t("div",rt,[a(m(Y),{class:"w-6 h-6 text-green-600"})])])]),_:1})]),_:1}),a(p,null,{default:o(()=>[a(x,{class:"p-6"},{default:o(()=>[t("div",ot,[t("div",null,[e[7]||(e[7]=t("p",{class:"text-sm font-medium text-gray-600 dark:text-gray-400"},"Total Transactions",-1)),t("p",dt,n(d.summary.total_transactions),1)]),t("div",lt,[a(m(z),{class:"w-6 h-6 text-blue-600"})])])]),_:1})]),_:1}),a(p,null,{default:o(()=>[a(x,{class:"p-6"},{default:o(()=>[t("div",nt,[t("div",null,[e[8]||(e[8]=t("p",{class:"text-sm font-medium text-gray-600 dark:text-gray-400"},"Completed",-1)),t("p",it,n(d.summary.completed_transactions),1)]),t("div",mt,[a(m(Q),{class:"w-6 h-6 text-green-600"})])])]),_:1})]),_:1}),a(p,null,{default:o(()=>[a(x,{class:"p-6"},{default:o(()=>[t("div",ut,[t("div",null,[e[9]||(e[9]=t("p",{class:"text-sm font-medium text-gray-600 dark:text-gray-400"},"Canceled",-1)),t("p",pt,n(d.summary.pending_transactions),1)]),t("div",xt,[a(m(Z),{class:"w-6 h-6 text-red-600"})])])]),_:1})]),_:1}),a(p,null,{default:o(()=>[a(x,{class:"p-6"},{default:o(()=>[t("div",gt,[t("div",null,[e[10]||(e[10]=t("p",{class:"text-sm font-medium text-gray-600 dark:text-gray-400"},"Avg Order Value",-1)),t("p",ct,n(i(d.summary.average_order_value)),1)]),t("div",yt,[a(m(q),{class:"w-6 h-6 text-purple-600"})])])]),_:1})]),_:1})]),a(p,{class:"mb-6"},{default:o(()=>[a(D,null,{default:o(()=>[a(P,null,{default:o(()=>e[11]||(e[11]=[b("Filters")])),_:1,__:[11]})]),_:1}),a(x,null,{default:o(()=>[t("div",ft,[t("div",null,[e[12]||(e[12]=t("label",{class:"block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"},"Time Period",-1)),a(m(f),{modelValue:l.period,"onUpdate:modelValue":[e[0]||(e[0]=s=>l.period=s),c],options:_},null,8,["modelValue"])]),t("div",null,[e[13]||(e[13]=t("label",{class:"block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"},"Customer",-1)),a(m(f),{modelValue:l.customer,"onUpdate:modelValue":[e[1]||(e[1]=s=>l.customer=s),c],options:d.customers},null,8,["modelValue","options"])]),t("div",null,[e[14]||(e[14]=t("label",{class:"block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"},"Sort By",-1)),a(m(f),{modelValue:l.sort_by,"onUpdate:modelValue":[e[2]||(e[2]=s=>l.sort_by=s),c],options:v},null,8,["modelValue"])]),t("div",null,[e[15]||(e[15]=t("label",{class:"block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"},"Sort Order",-1)),a(m(f),{modelValue:l.sort_order,"onUpdate:modelValue":[e[3]||(e[3]=s=>l.sort_order=s),c],options:h},null,8,["modelValue"])])])]),_:1})]),_:1}),a(p,null,{default:o(()=>[a(D,null,{default:o(()=>[a(P,null,{default:o(()=>e[16]||(e[16]=[b("Invoice Transactions")])),_:1,__:[16]})]),_:1}),a(x,null,{default:o(()=>[t("div",bt,[t("table",_t,[e[17]||(e[17]=t("thead",null,[t("tr",{class:"border-b dark:border-gray-700"},[t("th",{class:"text-left py-3 px-4 font-medium text-gray-900 dark:text-gray-100"},"Date"),t("th",{class:"text-left py-3 px-4 font-medium text-gray-900 dark:text-gray-100"},"Invoice # - Reference"),t("th",{class:"text-left py-3 px-4 font-medium text-gray-900 dark:text-gray-100"},"Customer"),t("th",{class:"text-right py-3 px-4 font-medium text-gray-900 dark:text-gray-100"},"Cash"),t("th",{class:"text-right py-3 px-4 font-medium text-gray-900 dark:text-gray-100"},"W/holding TAX 1%"),t("th",{class:"text-right py-3 px-4 font-medium text-gray-900 dark:text-gray-100"},"TAX 5%"),t("th",{class:"text-right py-3 px-4 font-medium text-gray-900 dark:text-gray-100"},"Sale - Non vat total"),t("th",{class:"text-right py-3 px-4 font-medium text-gray-900 dark:text-gray-100"},"VAT"),t("th",{class:"text-right py-3 px-4 font-medium text-gray-900 dark:text-gray-100"},"Running Balance"),t("th",{class:"text-left py-3 px-4 font-medium text-gray-900 dark:text-gray-100"},"Status")])],-1)),t("tbody",null,[(g(!0),y(X,null,E(d.transactions,s=>(g(),y("tr",{key:s.id,class:V(["border-b dark:border-gray-700",s.status==="Canceled"?"bg-red-50 dark:bg-red-900/20 hover:bg-red-100 dark:hover:bg-red-900/30":"hover:bg-gray-50 dark:hover:bg-gray-800"])},[t("td",vt,n(k(s.transaction_date)),1),t("td",ht,n(s.invoice_number),1),t("td",kt,[t("div",null,[t("div",wt,n(s.customer_name),1),s.company_name?(g(),y("div",Tt,n(s.company_name),1)):S("",!0)])]),t("td",Ct,n(i(s.cash_amount)),1),t("td",$t,n(i(s.withholding_tax)),1),t("td",St,n(i(s.tax_5_percent)),1),t("td",Vt,n(i(s.sale_non_vat_total)),1),t("td",zt,n(i(s.vat_amount)),1),t("td",Dt,n(i(s.running_balance)),1),t("td",Pt,[t("span",{class:V([B(s.status),"inline-flex px-2 py-1 text-xs font-semibold rounded-full"])},n(s.status),3)])],2))),128))])]),d.transactions.length===0?(g(),y("div",At,[t("div",Rt,[a(m(z),{class:"h-12 w-12"})]),e[18]||(e[18]=t("h3",{class:"mt-2 text-sm font-medium text-gray-900 dark:text-gray-100"},"No invoice transactions found",-1)),e[19]||(e[19]=t("p",{class:"mt-1 text-sm text-gray-500 dark:text-gray-400"},"Try adjusting your filters to see more results.",-1))])):S("",!0)])]),_:1})]),_:1})]),_:1}))}});export{qt as default};
