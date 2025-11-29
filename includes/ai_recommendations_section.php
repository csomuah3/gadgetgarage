<!-- AI-Powered Recommendations Section -->
<section class="ai-recommendations-section" style="background: linear-gradient(135deg, #f8f9ff 0%, #f0f2ff 100%); padding: 80px 0; margin: 100px 0 150px 0;">
	<div class="container">
		<div class="section-header text-center mb-5">
			<div style="display: flex; align-items: center; justify-content: center; gap: 15px; margin-bottom: 15px;">
				<i class="fas fa-brain" style="font-size: 2.5rem; color: #2563eb;"></i>
				<h2 class="section-title" style="font-size: 3rem; font-weight: 800; background: linear-gradient(135deg, #1e3a8a, #2563eb); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; margin: 0;">
					Recommended for You
				</h2>
			</div>
			<p class="section-subtitle" style="font-size: 1.2rem; color: #64748b; max-width: 600px; margin: 0 auto;">
				AI-powered personalized recommendations just for you
			</p>
		</div>

		<div id="aiRecommendationsContainer" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 30px; margin-top: 40px;">
			<!-- Loading state -->
			<div id="aiRecommendationsLoading" style="grid-column: 1 / -1; text-align: center; padding: 60px 20px;">
				<i class="fas fa-spinner fa-spin" style="font-size: 3rem; color: #2563eb; margin-bottom: 20px;"></i>
				<p style="font-size: 1.2rem; color: #64748b;">AI is analyzing your preferences...</p>
			</div>
		</div>
	</div>
</section>

<script>
// Load AI-Powered Recommendations
(function() {
	async function loadAIRecommendations() {
		const container = document.getElementById('aiRecommendationsContainer');
		const loading = document.getElementById('aiRecommendationsLoading');
		
		if (!container) return;

		// Get current product ID if on product page
		const urlParams = new URLSearchParams(window.location.search);
		const productId = urlParams.get('pid') || urlParams.get('id') || null;

		try {
			let url = '../actions/get_ai_recommendations.php';
			if (productId) {
				url += '?product_id=' + productId;
			}
			
			const response = await fetch(url);
			const data = await response.json();

			if (data.status === 'success' && data.products && data.products.length > 0) {
				// Hide loading
				if (loading) loading.style.display = 'none';

				// Clear container
				container.innerHTML = '';

				// Display products
				data.products.forEach(product => {
					const productCard = createProductCard(product);
					container.appendChild(productCard);
				});
			} else {
				// Show error or fallback
				if (loading) {
					loading.innerHTML = '<p style="font-size: 1.1rem; color: #64748b;">Unable to load recommendations at this time.</p>';
				}
			}
		} catch (error) {
			console.error('Error loading AI recommendations:', error);
			if (loading) {
				loading.innerHTML = '<p style="font-size: 1.1rem; color: #64748b;">Unable to load recommendations at this time.</p>';
			}
		}
	}

	// Create product card HTML
	function createProductCard(product) {
		const card = document.createElement('div');
		card.className = 'ai-recommendation-card';
		card.style.cssText = `
			background: white;
			border-radius: 16px;
			overflow: hidden;
			box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
			transition: all 0.3s ease;
			cursor: pointer;
		`;
		card.onmouseover = function() {
			this.style.transform = 'translateY(-8px)';
			this.style.boxShadow = '0 8px 30px rgba(37, 99, 235, 0.15)';
		};
		card.onmouseout = function() {
			this.style.transform = 'translateY(0)';
			this.style.boxShadow = '0 4px 20px rgba(0, 0, 0, 0.08)';
		};
		card.onclick = function() {
			window.location.href = `single_product.php?pid=${product.product_id}`;
		};

		const imageUrl = product.image_url || 'https://via.placeholder.com/300x300?text=Product';
		const price = parseFloat(product.product_price) || 0;

		card.innerHTML = `
			<div style="width: 100%; height: 250px; background: #f8fafc; display: flex; align-items: center; justify-content: center; overflow: hidden;">
				<img src="${imageUrl}" alt="${(product.product_title || 'Product').replace(/"/g, '&quot;')}" 
					 style="max-width: 100%; max-height: 100%; object-fit: contain;">
			</div>
			<div style="padding: 20px;">
				<h3 style="font-size: 1.2rem; font-weight: 700; color: #1f2937; margin-bottom: 10px; line-height: 1.4; min-height: 56px;">
					${(product.product_title || 'Product').replace(/</g, '&lt;').replace(/>/g, '&gt;')}
				</h3>
				<div style="display: flex; align-items: center; gap: 10px; margin-bottom: 15px; flex-wrap: wrap;">
					<span style="background: #eff6ff; color: #1e3a8a; padding: 6px 12px; border-radius: 8px; font-size: 0.85rem; font-weight: 600;">
						${(product.brand_name || 'Brand').replace(/</g, '&lt;').replace(/>/g, '&gt;')}
					</span>
					<span style="background: #f0fdf4; color: #166534; padding: 6px 12px; border-radius: 8px; font-size: 0.85rem; font-weight: 600;">
						${(product.cat_name || 'Category').replace(/</g, '&lt;').replace(/>/g, '&gt;')}
					</span>
				</div>
				<div style="font-size: 1.8rem; font-weight: 700; color: #2563eb; margin-bottom: 15px;">
					GH₵${price.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}
				</div>
				<button onclick="event.stopPropagation(); window.location.href='single_product.php?pid=${product.product_id}'" 
						style="width: 100%; background: linear-gradient(135deg, #2563eb, #1d4ed8); color: white; border: none; padding: 12px; border-radius: 10px; font-weight: 600; cursor: pointer; transition: all 0.3s ease;">
					<i class="fas fa-eye"></i> View Details
				</button>
			</div>
		`;

		return card;
	}

	// Load when DOM is ready
	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', loadAIRecommendations);
	} else {
		loadAIRecommendations();
	}
})();
</script>

