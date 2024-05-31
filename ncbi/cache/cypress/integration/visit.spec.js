
describe('download file', function(){

  it('Verify all website URLs', function(){
  
  
    cy.visit('https://www.ncbi.nlm.nih.gov/bioproject?term=(USDA%5BFunding%20Agency%5D%20OR%20NIFA%5BFunding%20Agency%5D%20OR%20APHIS%5BFunding%20Agency%5D%20OR%20USFS%5BFunding%20Agency%5D%20OR%20NRCS%5BFunding%20Agency%5D%20OR%20USDA*%5BSubmitter%20Organization%5D%20OR%20U.S.%20Department%20of%20Agriculture%5BSubmitter%20Organization%5D%20OR%20US%20Department%20of%20Agriculture%5BSubmitter%20Organization%5D%20OR%20Agricultural%20Research%20Service%5BSubmitter%20Organization%5D%20OR%20NIFA%5BSubmitter%20Organization%5D%20OR%20APHIS%5BSubmitter%20Organization%5D%20OR%20NRRL%5BTitle%5D%20OR%20NRRL%5BKeyword%5D%20OR%20NRRL%5BDescription%5D%20OR%20United%20States%20Department%20of%20Agriculture%5BFunding%20Agency%5D%20OR%20United%20States%20Department%20of%20Agriculture%5BSubmitter%20Organization%5D)');
          
          cy.get(".results_settings #sendto > a.tgt_dark").invoke('show').click({ multiple: true });
          cy.get('#send_to_menu').invoke('show').click({ multiple: true });
          cy.get("fieldset input#dest_File").click({ multiple: true });
          cy.get("#submenu_File").click({ multiple: true  });
          cy.get("select#file_format").select('xml');

     
           cy.window().document().then(function (doc) {
            doc.addEventListener('click', () => {
               setTimeout(function () { doc.location.reload() }, 50000)
             })
           cy.get("button[type='submit']").contains("Create File").click({ force: true });

     }) 

 
   
  });
});
