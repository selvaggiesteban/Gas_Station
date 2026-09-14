<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center px-6 py-3 bg-corporate-primary border border-transparent rounded-lg font-semibold text-sm text-corporate-background uppercase tracking-wide hover:bg-orange-600 focus:bg-orange-600 active:bg-orange-700 focus:outline-none focus:ring-2 focus:ring-corporate-primary focus:ring-offset-2 transition ease-in-out duration-150 shadow-md']) }}>
    {{ $slot }}
</button>
