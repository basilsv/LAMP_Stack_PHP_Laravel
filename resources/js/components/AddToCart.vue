<template>
    <div>
         <hr>
         <button class = "btn btn-warning text-center" v-on:click.prevent="addProductToCart()">Add To Cart</button>
    </div>
</template>

<script>
    export default {
        data(){
return {
    
}
        },
        props:['productId','userId'],
        methods:{
            async addProductToCart(){
                //Checking If user is logged in or not
                if(this.userId==0){
                    this.$toastr.e('You need to login To Add this product to Cart');
                    return;
                }
                
                console.log(this.productId);
                let response = await axios.post('/cart',{
                 'product_id':this.productId
                });
           
           this.$root.$emit('changeInCart',response.data.items);

            }

        },
        mounted() {
            console.log('Component mounted.')
        }
    }
</script>
