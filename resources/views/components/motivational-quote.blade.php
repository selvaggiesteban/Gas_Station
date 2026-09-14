<div x-data="{ 
    quote: '{{ __('Cargando frase motivadora...') }}', 
    author: '', 
    loading: true,
    fetchQuote() {
        fetch('{{ route('frases.aleatoria') }}')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    this.quote = data.quote;
                    this.author = data.author;
                }
                this.loading = false;
            })
            .catch(error => {
                console.error('Error fetching quote:', error);
                this.quote = '¡Sigue adelante, tú puedes!';
                this.author = 'AcademicUS';
                this.loading = false;
            });
    }
}" x-init="fetchQuote()" class="mb-8 bg-gradient-to-r from-corporate-primary/10 to-[#FF9933]/10 border-l-4 border-corporate-primary p-6 rounded-r-lg shadow-sm">
    <div class="flex items-start">
        <div class="flex-shrink-0 mr-4">
            <svg class="h-8 w-8 text-corporate-primary" fill="currentColor" viewBox="0 0 24 24">
                <path d="M14.017 21L14.017 18C14.017 16.8954 14.9124 16 16.017 16H19.017C19.5693 16 20.017 15.5523 20.017 15V9C20.017 8.44772 19.5693 8 19.017 8H16.017C15.4647 8 15.017 8.44772 15.017 9V12C15.017 12.5523 14.5693 13 14.017 13H11.017C10.4647 13 10.017 12.5523 10.017 12V9C10.017 7.89543 10.9124 7 12.017 7H19.017C20.1216 7 21.017 7.89543 21.017 9V15C21.017 17.2091 19.2261 19 17.017 19H14.017V21H14.017ZM3.01697 21L3.01697 18C3.01697 16.8954 3.9124 16 5.01697 16H8.01697C8.56925 16 9.01697 15.5523 9.01697 15V9C9.01697 8.44772 8.56925 8 8.01697 8H5.01697C4.46468 8 4.01697 8.44772 4.01697 9V12C4.01697 12.5523 3.56925 13 3.01697 13H0.0169678C-0.535317 13 -0.983032 12.5523 -0.983032 12V9C-0.983032 7.89543 -0.0876026 7 1.01697 7H8.01697C9.12154 7 10.017 7.89543 10.017 9V15C10.017 17.2091 8.2261 19 6.01697 19H3.01697V21H3.01697Z" />
            </svg>
        </div>
        <div>
            <p class="text-xl font-medium text-gray-800 italic" :class="{ 'animate-pulse': loading }">
                <span x-text="loading ? '...' : '&quot;' + quote + '&quot;'"></span>
            </p>
            <p class="mt-2 text-corporate-primary font-bold" x-show="!loading && author">
                — <span x-text="author"></span>
            </p>
        </div>
    </div>
</div>
